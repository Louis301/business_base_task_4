<?php
namespace App\Services;

use App\Models\Database;
use Exception;

class RequestService {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function createRequest(array $data): void {
        $this->db->insert('requests', [
            'client_name' => $data['client_name'],
            'phone' => $data['phone'],
            'address' => $data['address'],
            'problem_text' => $data['problem_text'],
            'status' => 'new',
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    public function getRequests(?int $userId = null, ?string $role = null): array {
        $where = [];
        
        if ($role === 'master' && $userId) {
            $where['assigned_to'] = $userId;
        }
        
        return $this->db->select('requests', '*', $where);
    }

    public function assignRequest(int $id, int $masterId): void {
        $current = $this->db->get('requests', 'status', ['id' => $id]);
        
        if ($current !== 'new') {
            throw new Exception("Можно назначить только заявку со статусом 'new'", 400);
        }

        $this->db->update('requests', [
            'status' => 'assigned',
            'assigned_to' => $masterId,
            'updated_at' => date('Y-m-d H:i:s')
        ], ['id' => $id]);
    }

    public function cancelRequest(int $id): void {
        $current = $this->db->get('requests', 'status', ['id' => $id]);
        
        if (!in_array($current, ['new', 'assigned'])) {
            throw new Exception("Нельзя отменить заявку в текущем статусе", 400);
        }

        $this->db->update('requests', [
            'status' => 'canceled',
            'updated_at' => date('Y-m-d H:i:s')
        ], ['id' => $id]);
    }

    /**
     * Ключевой метод с защитой от Race Condition (Задача BE-8)
     */
    public function takeRequest(int $id, int $masterId): bool {
        $pdo = $this->db->pdo;
        $pdo->beginTransaction();

        try {
            // 1. Проверяем текущий статус
            $current = $this->db->get('requests', 'status', ['id' => $id]);
            
            if ($current !== 'assigned') {
                throw new Exception("Заявка не в статусе 'assigned'", 409);
            }

            // 2. Условное обновление (Атомарная операция)
            // Обновляем ТОЛЬКО если статус всё ещё 'assigned'
            $updated = $this->db->update('requests', [
                'status' => 'in_progress',
                'updated_at' => date('Y-m-d H:i:s')
            ], [
                'id' => $id,
                'status' => 'assigned' // Критически важное условие WHERE!
            ]);

            // 3. Проверяем количество затронутых строк
            if ($updated === 0) {
                throw new Exception("Конфликт: заявку уже взял другой мастер", 409);
            }

            $pdo->commit();
            return true;

        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function completeRequest(int $id): void {
        $current = $this->db->get('requests', 'status', ['id' => $id]);
        
        if ($current !== 'in_progress') {
            throw new Exception("Можно завершить только заявку в работе", 400);
        }

        $this->db->update('requests', [
            'status' => 'done',
            'updated_at' => date('Y-m-d H:i:s')
        ], ['id' => $id]);
    }
}