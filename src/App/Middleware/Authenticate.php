<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Service\UserService;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Exception\HttpForbiddenException;


/**
 * Authenticate users
 */
class Authenticate {

    public function __construct(private readonly UserService $user_service) { }

    public function __invoke(Request $request, RequestHandler $handler){
        //Check if token is in header
        $token = $request->getHeaderLine('Token');
        if(empty($token)){
            throw new HttpForbiddenException($request, 'Token not found');
        }

        //Check if the token is currently considered authenticated
        $user_id = $this->user_service->is_authenticated($token);
        if(!$user_id){
            throw new HttpForbiddenException($request,'Token expired or invalid');
        }

        //Add the user id that the token belongs to in the request header
        $request = $request->withHeader('user_id', $user_id);
        return $handler->handle($request);
    }
}