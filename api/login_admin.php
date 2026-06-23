<?php
declare(strict_types=1);

/**
 * api/login_admin.php — AdryRanch
 * Autenticación del Dashboard administrativo. Sesiones nativas, JSON puro.
 */

require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/auth_dashboard.php';

iniciarSesionSegura();

function responderErrorLogin(string $mensaje, int $codigoHttp): void {
    http_response_code($codigoHttp);
    echo json_encode(["estatus" => "error", "mensaje" => $mensaje]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderErrorLogin('Método no permitido.', 405);
}

$cuerpo = json_decode((string)file_get_contents('php://input'), true);
if (!is_array($cuerpo)) {
    responderErrorLogin('Cuerpo de la solicitud inválido.', 422);
}

$email = trim((string)($cuerpo['email'] ?? ''));
$password = (string)($cuerpo['password'] ?? '');

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
    responderErrorLogin('Correo o contraseña inválidos.', 422);
}

$db = new Database();
$conn = $db->getConnection();

try {
    $stmt = $conn->prepare(
        "SELECT id_usuario, nombre, email, password_hash, rol FROM usuarios_dashboard WHERE email = :email"
    );
    $stmt->bindValue(':email', $email);
    $stmt->execute();
    $usuario = $stmt->fetch();
} catch (PDOException $excepcion) {
    error_log('[login_admin.php] ' . $excepcion->getMessage());
    responderErrorLogin('No pudimos validar tu acceso. Intenta de nuevo.', 500);
}

if (!$usuario || !password_verify($password, $usuario['password_hash'])) {
    responderErrorLogin('Credenciales inválidas.', 401);
}

session_regenerate_id(true);
$_SESSION['idUsuario'] = (int)$usuario['id_usuario'];
$_SESSION['nombre'] = $usuario['nombre'];
$_SESSION['rol'] = $usuario['rol'];

echo json_encode([
    "estatus" => "ok",
    "nombre" => $usuario['nombre'],
    "rol" => $usuario['rol'],
]);
