<?php
namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Psr\Http\Message\ResponseInterface as Response;

class AuthMiddleware {
    private array $allowedRoles;

    public function __construct(array $allowedRoles) {
        $this->allowedRoles = $allowedRoles;
    }

    public function __invoke(Request $request, Handler $handler): Response {
        $session = $request->getAttribute('session');
        
        if (!$session->get('user_id')) {
            $response = $request->getResponseFactory()->createResponse();
            $response->getBody()->write(json_encode(['error' => 'Не авторизован']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        $role = $session->get('role');
        if (!in_array($role, $this->allowedRoles)) {
            $response = $request->getResponseFactory()->createResponse();
            $response->getBody()->write(json_encode(['error' => 'Доступ запрещён']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }

        return $handler->handle($request);
    }
}