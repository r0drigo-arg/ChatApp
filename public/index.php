<?php

declare(strict_types=1);

use App\Middleware\Authenticate;
use Slim\Factory\AppFactory;
use DI\ContainerBuilder;
use Slim\Handlers\Strategies\RequestResponseArgs;
use App\Middleware\ResponseHeader;
use App\Controllers\ChatController;
use App\Controllers\UserController;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Slim\Routing\RouteCollectorProxy;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Psr16Cache;
use Psr\SimpleCache\CacheInterface;

define('APP_ROOT', dirname(__DIR__));

require_once APP_ROOT . '/vendor/autoload.php';

//Create cache
try {
    $builder = new ContainerBuilder();
    $container = $builder
        ->addDefinitions([
            CacheInterface::class => function () {
                $fileSystemAdapter = new FilesystemAdapter(
                    'app',
                    3600,
                    __DIR__ . '/../var/cache'
                );
                return new Psr16Cache($fileSystemAdapter);
            },
        ])
        ->build();
} catch (Exception $e) {
    echo $e->getMessage();
    exit(1);
}
AppFactory::setContainer($container);
$app = AppFactory::create();

//Path variables in the method call
$collector = $app->getRouteCollector();
$collector->setDefaultInvocationStrategy(new RequestResponseArgs());

$app->addRoutingMiddleware();
$app->addBodyParsingMiddleware();

//Add logger and error middleware
$logger = new Logger('app');
$streamHandler = new StreamHandler(APP_ROOT . '/var/log', 100);
$logger->pushHandler($streamHandler);

$error_middleware = $app->addErrorMiddleware(true, true, true, $logger);
$error_handler = $error_middleware->getDefaultErrorHandler();
$error_handler->forceContentType('application/json');
$error_middleware->setDefaultErrorHandler($error_handler);

//Middleware to set the response type to JSON
$app->add(new ResponseHeader);

//Chat API
$app->group("/api/chat", function (RouteCollectorProxy $group) {
    $group->get('', ChatController::class . ':get_all')->add(Authenticate::class);
    $group->post('', ChatController::class . ':create')->add(Authenticate::class);

    $group->group('/{id:[0-9]+}', function (RouteCollectorProxy $group) {
        $group->get('', ChatController::class . ':get_by_id')->add(Authenticate::class);
        $group->patch('', ChatController::class . ':update')->add(Authenticate::class);
        $group->delete('', ChatController::class . ':delete')->add(Authenticate::class);

        $group->post('/enter', ChatController::class . ':enter')->add(Authenticate::class);
        $group->post('/leave', ChatController::class . ':leave')->add(Authenticate::class);
        $group->post('/message', ChatController::class . ':send_message')->add(Authenticate::class);
        $group->get('/message', ChatController::class . ':get_message')->add(Authenticate::class);
    });
});

//User API
$app->group("/api/user", function (RouteCollectorProxy $group) {
    $group->post('', UserController::class . ':create');
    $group->get('', UserController::class . ':get');
});

$app->run();