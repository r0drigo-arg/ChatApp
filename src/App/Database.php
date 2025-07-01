<?php

declare(strict_types=1);

namespace App;

use PDO;

class Database{
    private string $db_path;

    public function __construct() {
       $this->db_path = getenv("DATABASE_PATH");
    }

    public function get_connection() : PDO {
        $pdo = new PDO("sqlite:" . $this->db_path);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return $pdo;
    }
}