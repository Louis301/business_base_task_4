<?php
// public/index.php

// Для отладки: показывать все ошибки
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../logs/php_errors.log');

require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use App\Middleware\SessionMiddleware;
use App\Controllers\RequestController;
use App\Middleware\AuthMiddleware;

$app = AppFactory::create();

// Подключение сессий (наш middleware)
$app->add(new SessionMiddleware());

// Парсинг JSON тела запроса
$app->addBodyParsingMiddleware();

// Обработка ошибок
$app->addErrorMiddleware(true, true, true);

// === Публичные маршруты ===

$app->get('/login', function ($request, $response) {
    $html = file_get_contents(__DIR__ . '/login.html');
    $response->getBody()->write($html);
    return $response->withHeader('Content-Type', 'text/html');
});

$app->post('/login', function ($request, $response) {
    $session = $request->getAttribute('session');
    $data = $request->getParsedBody();
    
    // Установка сессии
    $session->set('user_id', (int)$data['user_id']);
    $session->set('role', $data['role']);
    $session->set('username', $data['username']);
    
    // ✅ Правильный редирект для Slim 4
    $role = $data['role'];
    if ($role === 'dispatcher') {
        return $response->withHeader('Location', '/dispatcher')->withStatus(302);
    } else {
        return $response->withHeader('Location', '/master')->withStatus(302);
    }
});

$app->get('/logout', function ($request, $response) {
    $session = $request->getAttribute('session');
    $session->destroy();
    return $response->withHeader('Location', '/login')->withStatus(302);
});

// === Защищённые маршруты ===

$app->get('/create', function ($request, $response) {
    $html = file_get_contents(__DIR__ . '/create.html');
    $response->getBody()->write($html);
    return $response->withHeader('Content-Type', 'text/html');
})->add(new AuthMiddleware(['dispatcher']));

$app->get('/dispatcher', function ($request, $response) {
    $html = file_get_contents(__DIR__ . '/dispatcher.html');
    $response->getBody()->write($html);
    return $response->withHeader('Content-Type', 'text/html');
})->add(new AuthMiddleware(['dispatcher']));

$app->get('/master', function ($request, $response) {
    $html = file_get_contents(__DIR__ . '/master.html');
    $response->getBody()->write($html);
    return $response->withHeader('Content-Type', 'text/html');
})->add(new AuthMiddleware(['master']));

// === API маршруты ===

$container = $app->getContainer();
$requestController = new RequestController($container);

$app->post('/api/requests', [$requestController, 'create']);
$app->get('/api/requests', [$requestController, 'getAll']);
$app->post('/api/assign/{id}', [$requestController, 'assign']);
$app->post('/api/cancel/{id}', [$requestController, 'cancel']);
$app->post('/api/take/{id}', [$requestController, 'take']);
$app->post('/api/complete/{id}', [$requestController, 'complete']);

// === Статические файлы ===

$app->get('/css/{file}', function ($request, $response, $args) {
    $file = __DIR__ . '/css/' . $args['file'];
    if (file_exists($file)) {
        $response->getBody()->write(file_get_contents($file));
        return $response->withHeader('Content-Type', 'text/css');
    }
    return $response->withStatus(404);
});

$app->get('/js/{file}', function ($request, $response, $args) {
    $file = __DIR__ . '/js/' . $args['file'];
    if (file_exists($file)) {
        $response->getBody()->write(file_get_contents($file));
        return $response->withHeader('Content-Type', 'application/javascript');
    }
    return $response->withStatus(404);
});

$app->run();