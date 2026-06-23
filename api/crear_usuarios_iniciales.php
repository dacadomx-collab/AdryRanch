<?php
declare(strict_types=1);

/**
 * api/crear_usuarios_iniciales.php — AdryRanch
 * SCRIPT TEMPORAL DE UN SOLO USO. Limpia los accesos placeholder y deja
 * activos los dos accesos reales del Dashboard (David: super_admin,
 * Adry: admin) con contraseña hasheada en BCrypt. Bórralo de /api/
 * después de usarlo.
 *
 * Uso: http://localhost/AdryRanch/api/crear_usuarios_iniciales.php?token=TEST_TOKEN
 */

header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/env_loader.php';
$env = cargarEntornoSeguro();
$tokenRecibido = (string)($_GET['token'] ?? '');

if (!hash_equals((string)($env['TEST_TOKEN'] ?? ''), $tokenRecibido)) {
    http_response_code(403);
    echo json_encode(["estatus" => "error", "mensaje" => "Token de configuración inválido."]);
    exit;
}

$passwordInicial = (string)($env['SEED_PASSWORD_INICIAL'] ?? '');
if ($passwordInicial === '') {
    http_response_code(500);
    echo json_encode(["estatus" => "error", "mensaje" => "Falta SEED_PASSWORD_INICIAL en .env."]);
    exit;
}

require_once __DIR__ . '/conexion.php';

$db = new Database();
$conn = $db->getConnection();

$emailsPlaceholderObsoletos = ['david@adryranch.com', 'adry@adryranch.com'];
$cuentasReales = [
    ['nombre' => 'David', 'email' => 'dacadomx@gmail.com', 'rol' => 'super_admin'],
    ['nombre' => 'Adry', 'email' => 'adrirock75@gmail.com', 'rol' => 'admin'],
];

$resultado = [];

try {
    $limpiar = $conn->prepare("DELETE FROM usuarios_dashboard WHERE email IN (:e1, :e2)");
    $limpiar->bindValue(':e1', $emailsPlaceholderObsoletos[0]);
    $limpiar->bindValue(':e2', $emailsPlaceholderObsoletos[1]);
    $limpiar->execute();
} catch (PDOException $excepcion) {
    error_log('[crear_usuarios_iniciales.php][limpieza] ' . $excepcion->getMessage());
}

foreach ($cuentasReales as $cuenta) {
    try {
        $hash = password_hash($passwordInicial, PASSWORD_BCRYPT);

        $stmt = $conn->prepare(
            "INSERT INTO usuarios_dashboard (nombre, email, password_hash, rol)
             VALUES (:nombre, :email, :password_hash, :rol)
             ON DUPLICATE KEY UPDATE
                nombre = VALUES(nombre),
                password_hash = VALUES(password_hash),
                rol = VALUES(rol)"
        );
        $stmt->bindValue(':nombre', $cuenta['nombre']);
        $stmt->bindValue(':email', $cuenta['email']);
        $stmt->bindValue(':password_hash', $hash);
        $stmt->bindValue(':rol', $cuenta['rol']);
        $stmt->execute();

        $filasAfectadas = $stmt->rowCount();
        $estatusOperacion = match (true) {
            $filasAfectadas === 1 => 'creado',
            $filasAfectadas === 2 => 'actualizado',
            default => 'sin_cambios',
        };

        $resultado[] = [
            "nombre" => $cuenta['nombre'],
            "email" => $cuenta['email'],
            "rol" => $cuenta['rol'],
            "estatus" => $estatusOperacion,
        ];
    } catch (PDOException $excepcion) {
        error_log('[crear_usuarios_iniciales.php] ' . $excepcion->getMessage());
        $resultado[] = ["nombre" => $cuenta['nombre'], "email" => $cuenta['email'], "estatus" => "error"];
    }
}

echo json_encode([
    "estatus" => "ok",
    "mensaje" => "Cuentas reales activas con la contraseña inicial definida en SEED_PASSWORD_INICIAL.",
    "cuentas" => $resultado,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
