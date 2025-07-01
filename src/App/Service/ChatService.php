<?php

declare(strict_types=1);

namespace App\Service;

use App\Repositories\ChatRepository;
use Psr\SimpleCache\CacheInterface;

class ChatService {
    public function __construct(private readonly ChatRepository $chat_repository,
                                private readonly CacheInterface $cache) { }

    public function create(int $owner_id, string $name) : int|bool {
        try{
            $id = $this->chat_repository->create($owner_id, $name);

            if(!$id) return $id;

            $id = (int) $id;

            $enter = $this->enter($id, $owner_id);

            if(!$enter) {
                $this->delete($id, $owner_id);
                return false;
            }

            return $id;

        } catch (\Exception $e){
            throw new \Exception("Failed to create chat: " . $e->getMessage());
        }
    }

    public function get_all(int $user_id) : array {
        try{
            return $this->chat_repository->get_all($user_id);
        } catch (\Exception $e){
            throw new \Exception("Failed to get all chats: " . $e->getMessage());
        }
    }

    public function get_by_id(int $id) : array|bool {
        try{
            $chat = $this->chat_repository->get_by_id($id);

            if(!$chat) return false;

            $users = $this->get_users($id);
            $chat['users'] = array_column($users, 'username');

            return $chat;

        } catch (\Exception $e){
            throw new \Exception("Failed to get user: " . $e->getMessage());
        }
    }

    public function get_users(int $id) : array|bool
    {
        try{
            return $this->chat_repository->get_users($id);
        } catch (\Exception $e){
            throw new \Exception("Failed to get users: " . $e->getMessage());
        }
    }

    public function update(int $id, int $user_id, string $name) : bool {
        try{
            if(!$this->get_by_id($id)) return false;

            return $this->chat_repository->update($id, $user_id, $name);
        } catch (\Exception $e){
            throw new \Exception("Failed to update chat: " . $e->getMessage());
        }
    }

    public function delete(int $id, int $user_id) : bool {
        try{
            $chat = $this->get_by_id($id);

            if(!$chat) return false;

            return $this->chat_repository->delete($id, $user_id);
        } catch (\Exception $e){
            throw new \Exception("Failed to delete chat: " . $e->getMessage());
        }
    }



    public function enter(int $chat_id, int $user_id) : bool {
        try {
            $cache_key = 'user_in_chat_' . $chat_id . "_" . $user_id;
            $this->cache->delete($cache_key);

            return $this->chat_repository->enter($chat_id, $user_id);
        } catch (\Exception $e){
            throw new \Exception("Failed to enter chat: " . $e->getMessage());
        }
    }

    public function leave(int $chat_id, int $user_id) : bool {
        try {
            $user_in_chat = $this->is_user_in_chat($chat_id, $user_id);
            //If the user is not in the chat or is the owner, it can't leave
            if($user_in_chat === -1 || $user_in_chat === 1) return false;

            $cache_key = 'user_in_chat_' . $chat_id . "_" . $user_id;
            $this->cache->delete($cache_key);

            return $this->chat_repository->leave($chat_id, $user_id);
        }catch (\Exception $e){
            throw new \Exception("Failed to leave chat: " . $e->getMessage());
        }
    }

    public function is_user_in_chat(int $chat_id, int $user_id) : int|bool {
        try {
            $cache_key = 'user_in_chat_' . $chat_id . "_" . $user_id;
            $user_in_chat =  $this->cache->get($cache_key);

            if($user_in_chat !== NULL) return $user_in_chat;

            $user_in_chat = $this->chat_repository->is_user_in_chat($chat_id, $user_id);
            $user_in_chat = $user_in_chat['role'] ?? -1;

            $this->cache->set($cache_key, $user_in_chat, 60 * 5);

            return $user_in_chat;

        }catch (\Exception $e){
            throw new \Exception("Failed to check if user in chat: " . $e->getMessage());
        }
    }



    public function send_message(int $chat_id, int $user_id, string $message) : bool {
        try {
            $user_in_chat = $this->is_user_in_chat($chat_id, $user_id);

            if($user_in_chat === -1) return false;

            return $this->chat_repository->send_message($chat_id, $user_id, $message);

        }catch (\Exception $e){
            throw new \Exception("Failed to send a message: " . $e->getMessage());
        }
    }

    public function get_message(int $chat_id, int $user_id, string $from="") : array|bool {
        try {
            $user_in_chat = $this->is_user_in_chat($chat_id, $user_id);

            if(!$user_in_chat) return false;

            return $this->chat_repository->get_messages($chat_id, $from);

        }catch (\Exception $e){
            throw new \Exception("Failed to get messages: " . $e->getMessage());
        }
    }
}