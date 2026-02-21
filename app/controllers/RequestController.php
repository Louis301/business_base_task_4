<?php
namespace App\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Models\Database;
use App\Services\RequestService;

class RequestController {
    private RequestService $service;

    public function __construct($container) {
        $this->service = new RequestService();
    }

    public function create(Request $request, Response $response): Response {
        $data = $request->getParsedBody();
        
        try {
            $this->service->createRequest($data);
            $response->getBody()->write(json_encode(['success' => true]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
    }

    public function getAll(Request $request, Response $response): Response {
        $session = $request->getAttribute('session');
        $userId = $session->get('user_id');
        $role = $session->get('role');
        
        $requests = $this->service->getRequests($userId, $role);
        
        $response->getBody()->write(json_encode($requests));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function assign(Request $request, Response $response, array $args): Response {
        $id = (int)$args['id'];
        $data = $request->getParsedBody();
        $masterId = (int)$data['master_id'];
        
        try {
            $this->service->assignRequest($id, $masterId);
            $response->getBody()->write(json_encode(['success' => true]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
    }

    public function cancel(Request $request, Response $response, array $args): Response {
        $id = (int)$args['id'];
        
        try {
            $this->service->cancelRequest($id);
            $response->getBody()->write(json_encode(['success' => true]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
    }

    public function take(Request $request, Response $response, array $args): Response {
        $session = $request->getAttribute('session');
        $userId = $session->get('user_id');
        $id = (int)$args['id'];

        try {
            $this->service->takeRequest($id, $userId);
            $response->getBody()->write(json_encode(['success' => true]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus($e->getCode() ?: 500);
        }
    }

    public function complete(Request $request, Response $response, array $args): Response {
        $id = (int)$args['id'];
        
        try {
            $this->service->completeRequest($id);
            $response->getBody()->write(json_encode(['success' => true]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
    }
}