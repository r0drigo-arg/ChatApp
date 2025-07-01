<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Service\ChatService;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Valitron\Validator;

class ChatController
{
    private Validator $validator_chat;
    private Validator $validator_message;
    private Validator $validator_date;

    public function __construct(private readonly ChatService $chat_service)
    {
        $this->validator_chat = new Validator;
        $this->validator_chat->mapFieldsRules([
            'name' => ['required', ['lengthBetween', 3, 32]]
        ]);

        $this->validator_message = new Validator;
        $this->validator_message->mapFieldsRules([
            'message' => ['required', ['lengthBetween', 1, 1024]],
        ]);

        $this->validator_date = new Validator;
        $this->validator_date->mapFieldsRules([
            'from' => [['dateFormat', 'Y-m-d H:i:s']],
        ]);
    }

    public function create(Request $request, Response $response): Response
    {
        $user_id = $request->getHeaderLine("user_id");
        $body = $request->getParsedBody();

        $validator = $this->validator_chat->withData($body);

        if (!$validator->validate()) {
            $response->getBody()->write(json_encode($validator->errors()));
            return $response->withStatus(400);
        }

        $name = $body['name'];

        $id = $this->chat_service->create((int)$user_id, $name);

        $body = json_encode([
            'message' => $id ? 'Chat created' : 'Failed to create chat',
            'id' => $id,
        ]);

        $response->getBody()->write($body);

        return $id ? $response->withStatus(200) : $response->withStatus(400);

    }

    public function get_all(Request $request, Response $response): Response
    {
        $user_id = $request->getHeaderLine("user_id");

        $data = $this->chat_service->get_all((int)$user_id);
        $body = json_encode($data);

        $response->getBody()->write($body);

        return $response;
    }

    public function get_by_id(Request $request, Response $response, string $id): Response
    {
        $user_id = $request->getHeaderLine("user_id");
        $chat = $this->chat_service->get_by_id((int)$id);


        $body = json_encode($chat);

        $response->getBody()->write($body);

        return $chat ? $response : $response->withStatus(400);
    }

    public function update(Request $request, Response $response, string $id): Response
    {
        $user_id = $request->getHeaderLine("user_id");
        $body = $request->getParsedBody();

        $validator = $this->validator_chat->withData($body);

        if (!$validator->validate()) {
            $response->getBody()->write(json_encode($validator->errors()));
            return $response->withStatus(400);
        }

        $name = $body['name'];
        $res = $this->chat_service->update((int)$id, (int)$user_id, $name);

        $body = json_encode([
            'message' => $res ? 'Chat updated' : 'Failed to update chat',
        ]);

        $response->getBody()->write($body);

        return $res ? $response : $response->withStatus(400);
    }

    public function delete(Request $request, Response $response, string $id): Response
    {
        $user_id = $request->getHeaderLine("user_id");
        $res = $this->chat_service->delete((int)$id, (int)$user_id);

        $body = json_encode([
            'message' => $res ? 'Chat deleted' : 'Failed to delete chat',
        ]);

        $response->getBody()->write($body);

        return $res ? $response : $response->withStatus(400);
    }


    public function enter(Request $request, Response $response, string $id): Response
    {
        $user_id = $request->getHeaderLine("user_id");

        $res = $this->chat_service->enter((int)$id, (int)$user_id);

        $body = json_encode([
            'message' => $res ? 'Chat entered successfully' : 'Failed to enter chat',
        ]);

        $response->getBody()->write($body);

        return $res ? $response : $response->withStatus(400);
    }

    public function leave(Request $request, Response $response, string $id): Response
    {
        $user_id = $request->getHeaderLine("user_id");

        $res = $this->chat_service->leave((int)$id, (int)$user_id);

        $body = json_encode([
            'message' => $res ? 'Chat left successfully' : 'Failed to leave chat',
        ]);

        $response->getBody()->write($body);

        return $res ? $response : $response->withStatus(400);
    }


    public function send_message(Request $request, Response $response, string $id): Response
    {
        $user_id = $request->getHeaderLine("user_id");
        $body = $request->getParsedBody();

        $validator = $this->validator_message->withData($body);

        if (!$validator->validate()) {
            $response->getBody()->write(json_encode($validator->errors()));
            return $response->withStatus(400);
        }

        $message = $body['message'];
        $res = $this->chat_service->send_message((int)$id, (int)$user_id, $message);

        $body = json_encode([
            'message' => $res ? 'Message Sent' : 'Failed to send message',
        ]);

        $response->getBody()->write($body);

        return $res ? $response : $response->withStatus(400);
    }

    public function get_message(Request $request, Response $response, string $id) : Response
    {
        $user_id = $request->getHeaderLine("user_id");
        $params = $request->getQueryParams();

        $validator = $this->validator_date->withData($params);

        if (!$validator->validate()) {
            $response->getBody()->write(json_encode($validator->errors()));
            return $response->withStatus(400);
        }

        $from = $params['from'] ?? "";
        $messages = $this->chat_service->get_message((int)$id, (int)$user_id, $from);

        $body = json_encode([
            'messages' => $messages ?: [],
        ]);

        $response->getBody()->write($body);

        return $messages ? $response : $response->withStatus(400);
    }
}
