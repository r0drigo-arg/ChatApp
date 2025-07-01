<?php

declare(strict_types=1);

namespace App\Service;

use App\Repositories\UserRepository;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Random\RandomException;

class UserService {
    public function __construct(private readonly UserRepository $user_repository,
                                private readonly CacheInterface $cache) { }

    public function is_authenticated(string $token) : int|false {
        try{
            return $this->cache->get('user_' . $token, false);
        } catch (InvalidArgumentException $e) {
            throw new \Exception('Failed to authenticate: ' . $e->getMessage());
        }
    }

    public function create(string $username, string $password) : array|bool {
        try{
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            if(!$this->user_repository->create($username, $password_hash)) return false;

            return $this->get($username, $password);
        } catch (\Exception $e){
            throw new \Exception('Failed to create user: ' . $e->getMessage());
        }
    }

    public function get(string $username, string $password) : array|bool {
        try{
            $user = $this->user_repository->get($username);

            if($user['id'] === null) return false;

            if(!password_verify($password, $user['password_hash'])) return false;

            //Add to cache to simulate a user session
            $token = bin2hex(random_bytes(16));
            $cache_key = 'user_' . $token;
            $this->cache->set($cache_key, $user['id'], 3600);

            unset($user['password_hash']);
            unset($user['id']);

            $user['token'] = $token;

            return $user;
        }
        catch (InvalidArgumentException|RandomException $e) {
            throw new \Exception('Failed to start session: ' . $e->getMessage());
        }
    }
}