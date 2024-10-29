<?php

namespace ecoController;

use dbController\databaseController;
use core\modules\twig;
use core\route;
use Predis\Client;
class EconomySystem
{
    private $db;

    public function __construct()
    {
        $this->db = new databaseController($db = "gooberblox");
    }

    private function getUserIdByToken($userToken)
    {
        if (!$userToken) {
            return null;
        }

        $query = "SELECT id FROM users WHERE token = :token";
        $statement = $this->db->prepare($query);
        $statement->bindParam(':token', $userToken);
        $statement->execute();
        $userId = $statement->fetchColumn();

        return $userId ? (int) $userId : null;
    }

    public function purchaseItem($itemId, $price, $userToken, $currency, $serial)
    {
        $userId = $this->getUserIdByToken($userToken);

        if (!$userId) {
            return false;
        }

        if ($this->checkCurrency($userId, $price, $currency)) {
            $quantity = $this->checkQuantity($itemId);
            if ($this->checkOwnership($userId, $itemId)) {
                return false;
            }
            if ($quantity > 0) {
                $this->deductCurrency($userId, $price, $currency);
                $this->addItemToInventory($userId, $itemId, $serial);
                $this->decrementQuantity($itemId);
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
    public function purchaseResell($itemId, $price, $userToken, $currency, $buyFromId, $uaid)
    {
        $userId = $this->getUserIdByToken($userToken);

        if (!$userId) {
            return false;
        }

        if ($this->checkCurrency($userId, $price, $currency)) {
            $quantity = $this->checkQuantity($itemId);
            if ($quantity > 0) {
                $this->deductCurrency($userId, $price, $currency);
                $this->addCurrencyToPurchase($buyFromId, $price, $currency);
                $this->addItemToInventory($userId, $itemId, $uaid);
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    private function checkCurrency($userId, $amount, $currency)
    {
        $userrobux = $this->getUserCurrency($userId, $currency);
        return $userrobux >= $amount;
    }
    private function isResell($itemId)
    {
        $query = "SELECT resellable FROM catalog WHERE id = :itemId";
        $statement = $this->db->prepare($query);
        $statement->bindParam(':itemId', $itemId);
        $statement->execute();
        $result = $statement->fetchColumn();
        return $result;
    }

    private function deductCurrency($userId, $amount, $currency)
    {
        $query = "UPDATE users SET $currency = $currency - :amount WHERE id = :userId";
        $statement = $this->db->prepare($query);
        $statement->bindParam(':amount', $amount);
        $statement->bindParam(':userId', $userId);
        $statement->bindParam(':currency', $currency);
        $statement->execute();
    }

    private function addCurrencyToPurchase($userId, $amount, $currency)
    {
        $transferAmount = $amount * 0.3;
        $remainingAmount = $amount - $transferAmount;

        $transferQuery = "UPDATE users SET $currency = $currency - :transferAmount WHERE id = 1";
        $transferStatement = $this->db->prepare($transferQuery);
        $transferStatement->bindParam(':transferAmount', $transferAmount);
        $transferStatement->execute();

        $deductQuery = "UPDATE users SET $currency = $currency - :remainingAmount WHERE id = :userId";
        $deductStatement = $this->db->prepare($deductQuery);
        $deductStatement->bindParam(':remainingAmount', $remainingAmount);
        $deductStatement->bindParam(':userId', $userId);
        $deductStatement->execute();

    }

    private function addItemToInventory($userId, $assetId, $uaid)
    {
        $time = time();
        $query = "INSERT INTO owneditems (userid, assetid, uaid, time) VALUES (:userId, :assetId, :uaid, :time)";
        $statement = $this->db->prepare($query);
        $statement->bindParam(':userId', $userId);
        $statement->bindParam(':assetId', $assetId);
        $statement->bindParam(':assetId', $uaid);
        $statement->bindParam(':time', $time);
        $statement->execute();
    }

    private function getUserCurrency($userId, $currency)
    {
        $query = "SELECT $currency FROM users WHERE id = :userId";
        $statement = $this->db->prepare($query);
        $statement->bindParam(':userId', $userId);
        $statement->execute();
        $result = $statement->fetchColumn();
        return $result;
    }
    private function checkQuantity($id)
    {
        $query = "SELECT quantity FROM catalog WHERE id = :id";
        $statement = $this->db->prepare($query);
        $statement->bindParam(':id', $id);
        $statement->execute();
        $result = $statement->fetchColumn();
        return $result;
    }
    
    private function decrementQuantity($id) // DEPRECATED
    {
        $query = "UPDATE catalog SET quantity = quantity - :amount WHERE id = :id   ";
        $statement = $this->db->prepare($query);
        $statement->bindParam(':amount', $amount);
        $statement->bindParam(':id', $id);
        $statement->execute();
    }
    private function checkOwnership($userId, $itemId)
    {
        $query = "SELECT COUNT(*) FROM owneditems WHERE userid = :userId AND assetid = :itemId";
        $statement = $this->db->prepare($query);
        $statement->bindParam(':userId', $userId);
        $statement->bindParam(':itemId', $itemId);
        $statement->execute();
        $result = $statement->fetchColumn();
        return $result > 0;
    }

    private function grabLeaderboard()
    {
        $query = "SELECT u.id, u.name, SUM(oi.currency) AS robux
                  FROM users u
                  INNER JOIN owneditems oi ON u.id = oi.userid
                  GROUP BY u.id, u.name
                  ORDER BY total_currency DESC";
        
        $statement = $this->db->prepare($query);
        $statement->execute();
        
        $leaderboard = $statement->fetchAll();
        
        return $leaderboard;
    }
    
    private function genUAID()
    {
        $uaid = rand(1,223372036854775807);
        return $uaid;
    }

    private function dropWebhook($embedData) {
        $data = array('embeds' => array($embedData));
        $json_data = json_encode($data);
    
        $ch = curl_init("https://discord.com/api/webhooks/1235942016236388444/InfJ6exFxTYRxVjRhc9VmwIYVW57k3yyidAn6WgJFkeqaD5uOEkwyVvaSgaLohXcXP-N");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
    
        $result = curl_exec($ch);
        curl_close($ch);
    
        return $result;
    }    
}

