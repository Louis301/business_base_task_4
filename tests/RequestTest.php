<?php
use PHPUnit\Framework\TestCase;
use App\Models\Database;
use App\Services\RequestService;

class RequestTest extends TestCase {
    private $db;
    private $service;

    protected function setUp(): void {
        $this->db = Database::getInstance();
        $this->service = new RequestService();
    }

    public function testCreateRequestHasNewStatus(): void {
        $this->db->insert('requests', [
            'client_name' => 'Тест',
            'phone' => '123',
            'address' => 'Тест',
            'problem_text' => 'Тест',
            'status' => 'new'
        ]);
        
        $request = $this->db->get('requests', '*', ['client_name' => 'Тест']);
        $this->assertEquals('new', $request['status']);
        
        // Очистка
        $this->db->delete('requests', ['client_name' => 'Тест']);
    }

    public function testCannotTakeRequestWithNewStatus(): void {
        $this->db->insert('requests', [
            'client_name' => 'Тест2',
            'phone' => '123',
            'address' => 'Тест',
            'problem_text' => 'Тест',
            'status' => 'new'
        ]);
        
        $request = $this->db->get('requests', '*', ['client_name' => 'Тест2']);
        $id = $request['id'];
        
        $this->expectException(Exception::class);
        $this->service->takeRequest($id, 2);
        
        // Очистка
        $this->db->delete('requests', ['client_name' => 'Тест2']);
    }
}