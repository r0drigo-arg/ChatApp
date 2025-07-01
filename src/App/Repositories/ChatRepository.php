<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database;
use PDO;

class ChatRepository {
    public function __construct(private Database $database) { }

    public function create(int $owner_id, string $name) : string|bool {

        $sql = 'INSERT INTO CHAT (owner_id, created_at, name) 
                VALUES (:owner_id, :created_at, :name);';

        $pdo = $this->database->get_connection();
        $stmt = $pdo->prepare($sql);

        $created_at = date('Y-m-d H:i:s');

        $stmt->bindParam(':owner_id', $owner_id, PDO::PARAM_INT);
        $stmt->bindParam(':created_at', $created_at, PDO::PARAM_STR);
        $stmt->bindParam(':name', $name,PDO::PARAM_STR);

        $stmt->execute();

        return $pdo->lastInsertId();
    }

    public function get_all(int $user_id) : array {

        $sql = "SELECT C.id, C.name,
                MAX(CASE WHEN CU.user_id = :user_id THEN 1 ELSE 0 END) AS is_member,
                COUNT(CU.user_id) AS total_members

                FROM CHAT AS C
                LEFT JOIN CHAT_USER AS CU ON C.id = CU.chat_id
                GROUP BY C.id
                ORDER BY C.id;";

        $pdo = $this->database->get_connection();
        $stmt = $pdo->prepare($sql);

        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function get_by_id(int $id) : array|bool {
        $sql = 'SELECT
                C.id,
                C.name,
                C.owner_id,
                COUNT(CU.user_id) AS total_members
                
                FROM CHAT AS C
                LEFT JOIN CHAT_USER AS CU ON C.id = CU.chat_id
                WHERE C.id = :id
                GROUP BY C.id
                ORDER BY C.id;';

        $pdo = $this->database->get_connection();
        $stmt = $pdo->prepare($sql);

        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function get_users(int $id) : array{
        $sql = 'SELECT U.username
                FROM CHAT_USER AS CU
                LEFT JOIN USER AS U ON CU.user_id = U.id
                WHERE CU.chat_id = :id';

        $pdo = $this->database->get_connection();
        $stmt = $pdo->prepare($sql);

        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function update(int $id, int $owner_id, string $name) : bool {
        $sql = 'UPDATE CHAT SET name = :name WHERE id = :id AND owner_id = :owner_id;';

        $pdo = $this->database->get_connection();
        $stmt = $pdo->prepare($sql);

        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':owner_id', $owner_id, PDO::PARAM_INT);
        $stmt->bindParam(':name', $name,PDO::PARAM_STR);

        $stmt->execute();

        return $stmt->rowCount() == 1;
    }

    public function delete(int $id, int $owner_id) : bool {
        $sql = 'DELETE FROM CHAT WHERE id = :id AND owner_id = :owner_id;';

        $pdo = $this->database->get_connection();
        $stmt = $pdo->prepare($sql);

        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':owner_id', $owner_id, PDO::PARAM_INT);

        $stmt->execute();

        return $stmt->rowCount() == 1;
    }



    public function enter(int $chat_id, int $user_id) : bool {
        $sql = 'INSERT INTO CHAT_USER (chat_id, user_id, joined_at)
                VALUES (:chat_id, :user_id, :joined_at);';

        $pdo = $this->database->get_connection();
        $stmt = $pdo->prepare($sql);

        $joined_at = date('Y-m-d H:i:s');

        $stmt->bindParam(':chat_id', $chat_id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':joined_at', $joined_at,PDO::PARAM_STR);


        $stmt->execute();

        return $stmt->rowCount() == 1;
    }

    public function leave(int $chat_id, int $user_id) : bool {
        $sql = 'DELETE FROM CHAT_USER WHERE chat_id = :chat_id AND user_id = :user_id;';

        $pdo = $this->database->get_connection();
        $stmt = $pdo->prepare($sql);

        $stmt->bindParam(':chat_id', $chat_id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);

        $stmt->execute();

        return $stmt->rowCount() == 1;
    }

    public function is_user_in_chat(int $chat_id, int $user_id) : array|bool
    {
        $sql = 'SELECT MAX(CASE WHEN CHAT.owner_id = :user_id THEN 1 ELSE 0 END) AS role
                FROM CHAT_USER
                LEFT JOIN CHAT ON CHAT.id = CHAT_USER.chat_id
                WHERE chat_id = :chat_id AND user_id = :user_id;';

        $pdo = $this->database->get_connection();
        $stmt = $pdo->prepare($sql);

        $stmt->bindParam(':chat_id', $chat_id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);

        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }



    public function send_message(int $chat_id, int $user_id, string $message) : bool {
        $sql = 'INSERT INTO CHAT_MESSAGE (chat_id, user_id, message, sent_at)
                VALUES (:chat_id, :user_id, :message, :sent_at);';

        $pdo = $this->database->get_connection();
        $stmt = $pdo->prepare($sql);

        $sent_at = date('Y-m-d H:i:s');

        $stmt->bindParam(':chat_id', $chat_id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':message', $message,PDO::PARAM_STR);
        $stmt->bindParam(':sent_at', $sent_at,PDO::PARAM_STR);

        return $stmt->execute();
    }

    public function get_messages(int $chat_id, string $from = "") : array {
        $sql = 'SELECT message, sent_at
                FROM CHAT_MESSAGE
                WHERE chat_id = :chat_id
                AND sent_at >= :from
                ORDER BY sent_at DESC;';

        $pdo = $this->database->get_connection();
        $stmt = $pdo->prepare($sql);

        $stmt->bindParam(':chat_id', $chat_id, PDO::PARAM_INT);
        $stmt->bindParam(':from', $from,PDO::PARAM_STR);

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

}