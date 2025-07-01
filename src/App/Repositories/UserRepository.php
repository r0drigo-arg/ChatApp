<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database;
use PDO;

class UserRepository {
    public function __construct(private Database $database) { }

    public function create(string $username, string $password_hash) : bool {

        $sql = 'INSERT INTO USER (joined_at, username, password_hash)
                VALUES (:joined_at, :username, :password_hash);';

        $pdo = $this->database->get_connection();
        $stmt = $pdo->prepare($sql);

        $joined_at = date('Y-m-d H:i:s');

        $stmt->bindParam(':joined_at', $joined_at, PDO::PARAM_STR);
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->bindParam(':password_hash', $password_hash,PDO::PARAM_STR);

        return $stmt->execute();
    }

    public function get(string $username) : array|bool {

        $sql = 'SELECT * FROM USER WHERE username = :username';

        $pdo = $this->database->get_connection();
        $stmt = $pdo->prepare($sql);

        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}