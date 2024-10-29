<?php

namespace dbController;

use core\conf;
use PDO;
use PDOException;
use PDOStatement;

class databaseController
{
    private ?PDO $pdo;

    public function __construct(string|null $db) {
        if($db == null) {
            $db = conf::get()['project']['database']['db'];
        } 
        $host = conf::get()['project']['database']['host'];
        $port = conf::get()['project']['database']['port'];
        $user = conf::get()['project']['database']['user'];
        $password = conf::get()['project']['database']['password'];
        try {
            $this->pdo = new PDO("pgsql:host=$host;port=$port;dbname=$db;user=$user;password=$password");
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $exception) {
            echo "Connection to the database has failed: " . $exception->getMessage();
            $this->pdo = null;
        }
    }

    // p.s dont use yet, i am working on binding rn.
    // executing each statement manually is a pain. lets simplify it! :D
    // Okay! sounds good!
    // todo: make a new class for each table instead smelly (maybe)
    public function executeStatement(string $query, mixed $bindParamKey, mixed $bindParamValue): bool|PDOStatement|null {
      $statement = self->prepare($query);
      $statement->bindParam($bindParamKey, $bindParamValue);
      $statement->execute();
      return $statement;
    }

    public function prepare(string $query): false|PDOStatement|null
    {
        return $this->pdo?->prepare($query);
    }
}