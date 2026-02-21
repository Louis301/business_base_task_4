<?php
// app/controllers/RequestController.php

namespace App\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Services\RequestService;

class RequestController {
    private RequestService $service;

    public function __construct() {
        $this->service = new RequestService();
    }

    public function create(Request $request, Response $response): Response {
        $data = $request->getParsedBody();
        try {
            $this->service->createRequest($data);
            return $this->json($response, ['success' => true], 201);
        } catch (\Exception $e) {
            return $this->json($response, ['error' => $e->getMessage()], 400);
        }
    }

    public function getAll(Request $request, Response $response): Response {
        $session = $request->getAttribute('session');
        $requests = $this->service->getRequests(
            $session->get('user_id'),
            $session->get('role')
        );
        return $this->json($response, $requests);
    }

    public function assign(Request $request, Response $response, array $args): Response {
        $id = (int)$args['id'];
        $data = $request->getParsedBody();
        $masterId = (int)($data['master_id'] ?? 0);
        
        try {
            $this->service->assignRequest($id, $masterId);
            return $this->json($response, ['success' => true]);
        } catch (\Exception $e) {
            return $this->json($response, ['error' => $e->getMessage()], 400);
        }
    }

    public function cancel(Request $request, Response $response, array $args): Response {
        $id = (int)$args['id'];
        try {
            $this->service->cancelRequest($id);
            return $this->json($response, ['success' => true]);
        } catch (\Exception $e) {
            return $this->json($response, ['error' => $e->getMessage()], 400);
        }
    }

    public function take(Request $request, Response $response, array $args): Response {
        $session = $request->getAttribute('session');
        
        // ✅ Проверка авторизации
        if (!$session || !$session->get('user_id')) {
            return $this->json($response, ['error' => 'Не авторизован'], 401);
        }
        
        $userId = (int)$session->get('user_id');
        $id = (int)$args['id'];

        try {
            $this->service->takeRequest($id, $userId);
            return $this->json($response, ['success' => true]);
        } catch (\Exception $e) {
            $code = $e->getCode() ?: 500;
            return $this->json($response, ['error' => $e->getMessage()], $code);
        }
    }

    public function complete(Request $request, Response $response, array $args): Response {
        $id = (int)$args['id'];
        try {
            $this->service->completeRequest($id);
            return $this->json($response, ['success' => true]);
        } catch (\Exception $e) {
            return $this->json($response, ['error' => $e->getMessage()], 400);
        }
    }

    /**
     * ✅ Helper-метод для JSON-ответов (ОБЯЗАТЕЛЬНО ДОБАВИТЬ!)
     */
    private function json(Response $response, array $data, int $status = 200): Response {
        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));
        return $response
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withStatus($status);
    }
}