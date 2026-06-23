<?php
declare(strict_types=1);

/**
 * Conexión PDO centralizada — AdryRanch
 * Lee credenciales desde .env (Mandamiento 12 — Bóveda de Secretos),
 * vía el localizador defensivo multirruta de env_loader.php.
 */

require_once __DIR__ . '/env_loader.php';

class Database {
    private string $host;
    private string $db_name;
    private string $username;
    private string $password;
    private string $allowed_origins;
    public ?PDO $conn = null;

    public function __construct() {
        $env = cargarEntornoSeguro();

        $this->host = (string)($env['DB_HOST'] ?? 'localhost');
        $this->db_name = (string)($env['DB_NAME'] ?? '');
        $this->username = (string)($env['DB_USER'] ?? '');
        $this->password = (string)($env['DB_PASS'] ?? '');
        $this->allowed_origins = (string)($env['ALLOWED_ORIGINS'] ?? '');

        $this->setCorsHeaders();
    }

    private function jsonError(string $message, int $httpCode = 500): void {
        http_response_code($httpCode);
        echo json_encode(["status" => "error", "message" => $message]);
        exit;
    }

    private function setCorsHeaders(): void {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        $allowedList = explode(',', $this->allowed_origins);

        if (!empty($origin) && !in_array($origin, $allowedList, true)) {
            $this->jsonError("Acceso denegado: Origen no autorizado.", 403);
        }

        if (in_array($origin, $allowedList, true)) {
            header("Access-Control-Allow-Origin: " . $origin);
        } else {
            header("Access-Control-Allow-Origin: " . ($allowedList[0] ?? '*'));
        }

        header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type");
        header("Vary: Origin");
        header("Content-Type: application/json; charset=UTF-8");

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }

    public function getConnection(): PDO {
        if (empty($this->db_name) || empty($this->username)) {
            $this->jsonError("Error de BD: credenciales incompletas.");
        }

        try {
            $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset=utf8mb4";
            $this->conn = new PDO($dsn, $this->username, $this->password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $exception) {
            $this->jsonError("Error de conexión a la base de datos.");
        }

        return $this->conn;
    }
}
