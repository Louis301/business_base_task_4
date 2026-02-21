<?php
// app/middleware/SessionMiddleware.php
namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Psr\Http\Message\ResponseInterface as Response;

class SessionMiddleware {
    public function __invoke(Request $request, Handler $handler): Response {
        // Запускаем сессию, если ещё не запущена
        if (session_status() === PHP_SESSION_NONE) {
            if (session_id() === '') {
                session_set_cookie_params([
                    'lifetime' => 0,
                    'path' => '/',
                    'secure' => false,
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]);
                session_start();
            }
        }
        
        // Добавляем обёртку для доступа к сессии через запрос
        $session = new class {
            public function get(string $key, $default = null) {
                return $_SESSION[$key] ?? $default;
            }
            public function set(string $key, $value): void {
                $_SESSION[$key] = $value;
            }
            public function has(string $key): bool {
                return isset($_SESSION[$key]);
            }
            public function destroy(): void {
                session_destroy();
                $_SESSION = [];
            }
        };
        
        $request = $request->withAttribute('session', $session);
        
        return $handler->handle($request);
    }
}