<?php
use core\route;
use core\conf;
use core\modules\twig;
use dbController\databaseController;

class DonationController {

    public function createPromo($type) {
        $code = uuidv4();
        $db = new databaseController("gooberblox");
        
        $query = "INSERT INTO promo (product_key, creation, type) VALUES (:uuid, :timestamp, :type)";
        $stmt = $db->prepare($query);
        $result = $stmt->execute([
            ':uuid' => $code, 
            ':timestamp' => time(),
            ':type' => $type
        ]);
    
        if ($result) {
            return $code;
        } else {
            return false;
        }
    }
    
}
route::any("/admin/donationwebhook", function () {
    header('content-type: application/json');
    $donation = file_get_contents("php://input");
    $donation = urldecode($donation);
    $donation = str_replace('data =', '', $donation);
    $place = __FWDIR__ .'/files/kofi.txt';
    file_put_contents($place, $donation);
    $accessKey = conf::get()['project']['auth']['kofi'];

    if (stripos($donation, $accessKey) !== false) {
        $db = new databaseController("gooberblox");
        
        $data = json_decode($donation, true);
        
        $uuid = uuidv4();
        $email = $data['email'];
        $amount = $data['amount'];
        $timestamp = $data['timestamp'];
        $kofi_transaction_id = $data['kofi_transaction_id'];
        $tier_name = $data['tier_name'];
        $url = $data['url'];
        
        $query = "INSERT INTO donations (product_code, email, amount, timestamp, kofi_transaction_id, tier_name, url) 
        VALUES (:uuid, :email, :amount, :timestamp, :kofi_transaction_id, :tier_name, :url)";
        $stmt = $db->prepare($query);
        $result = $stmt->execute([
            ':uuid' => $uuid,
            ':email' => $email,
            ':amount' => $amount,
            ':timestamp' => $timestamp,
            ':kofi_transaction_id' => $kofi_transaction_id,
            ':tier_name' => $tier_name,
            ':url' => $url
        ]);
        if ($result) {
            return json_encode(["success" => true, "message" => "Donation data inserted successfully."]);
        } else {
            http_response_code(400);
            return json_encode(["success" => false, "message" => "Cannot update database"]);
        }
    } else {
        http_response_code(403);
        return json_encode([
            "success"=> false,
            "message" => "You do not have permission to access this resource."
        ]);
    }
});


route::post("/admin/createcard", function () {
    $donationController = new DonationController();
    return $donationController->createPromo("OBC"); 
});

route::post("/redeem", function () {
        header('content-type: application/json');
        (string)$code = (string)$_POST["code"];
        $userToken = $_COOKIE['user_token'] ?? null;
        $user = getUserByToken($userToken);
        if ($user) {
        $db = new databaseController("gooberblox");

        $query = "SELECT * FROM promo WHERE product_key = :code AND redeemed = FALSE";
        $stmt = $db->prepare($query);
        $stmt->execute([':code' => $code]);
        $promoData = $stmt->fetch();

        if ($promoData) {
            $type = $promoData['type'];
            $updateQuery = "UPDATE promo SET redeemed = TRUE, redeemTime = :redeemTime WHERE product_key = :code";
            $updateStmt = $db->prepare($updateQuery);
            $updateResult = $updateStmt->execute([':code' => $code, ':redeemTime' => time()]);

            if ($updateResult) {
                return json_encode(["success" => true, "type" => $type]);
            } else {
                http_response_code(500);
                return json_encode(["success" => false, "message" => "Error marking code as redeemed"]);
            }
        } else {
            http_response_code(400);
            return json_encode(["status" => "invalid"]);
        }
    }
    else {
        http_response_code(403);
        return json_encode(["success" => false, "message" => "User not authenticated."]);
    }
});
