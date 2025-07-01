<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Service\UserService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Valitron\Validator;

class UserController {
    private Validator $validator;

    public function __construct(private readonly UserService $user_service) {
        $this->validator = new Validator;
        $this->validator->mapFieldsRules([
            'username' => ['required', ['lengthBetween', 3, 32], 'alphaNum'],
            'password' => ['required', ['lengthBetween', 3, 32], 'alphaNum']
        ]);
    }

    public function create(Request $request, Response $response) : Response
    {;
        $body = $request->getParsedBody();

        $validator = $this->validator->withData($body);

        if(!$validator->validate()){
            $response->getBody()->write(json_encode($validator->errors()));
            return $response->withStatus(400);
        }

        $username = $body['username'];
        $password = $body['password'];

        $user = $this->user_service->create( $username, $password);

        $body = json_encode([
            'message' => 'User created',
            'user' => $user
        ]);

        $response->getBody()->write($body);

        return $user ? $response->withStatus(201) : $response->withStatus(400);
    }

    public function get(Request $request, Response $response) : Response {

        $param = $request->getQueryParams();

        $validator = $this->validator->withData($param);
        if(!$validator->validate()){
            $response->getBody()->write(json_encode($validator->errors()));
            return $response->withStatus(400);
        }

        $username = $param['username'];
        $password = $param['password'];

        $user = $this->user_service->get($username, $password);


        $param = json_encode([
            'message' => $user ? 'Authentication successful' : 'Authentication failed',
            'user' => $user
        ]);

        $response->getBody()->write($param);

        return $user ? $response : $response->withStatus(400);
    }

}