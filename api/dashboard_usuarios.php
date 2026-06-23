<?php
declare(strict_types=1);

/**
 * api/dashboard_usuarios.php — AdryRanch
 * Módulo C del Dashboard: User Creator Panel — exclusivo Super Admin.
 * GET  → lista usuarios_dashboard (nunca password_hash).
 * POST → { nombre, email, password, rol } crea admin o staff.
 */

require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/auth_dashboard.php';

function responderErrorUsuarios(string $mensaje, int $codigoHttp): void {
    http_response_code($codigoHttp);
    echo json_encode(["estatus" => "error", "mensaje" => $mensaje]);
    exit;
}

$sesion = requireSesionActiva();
requireRol($sesion, ['super_admin']);

$db = new Database();
$conn = $db->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $conn->query(
        "SELECT id_usuario, nombre, email, rol, creado_at FROM usuarios_dashboard ORDER BY creado_at DESC"
    );
    $filas = $stmt->fetchAll();

    $usuarios = array_map(static function (array $fila): array {
        return [
            "idUsuario" => (int)$fila['id_usuario'],
            "nombre" => $fila['nombre'],
            "email" => $fila['email'],
            "rol" => $fila['rol'],
            "creadoAt" => $fila['creado_at'],
        ];
    }, $filas);

    echo json_encode(["estatus" => "ok", "usuarios" => $usuarios]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cuerpo = json_decode((string)file_get_contents('php://input'), true);
    if (!is_array($cuerpo)) {
        responderErrorUsuarios('Cuerpo de la solicitud inválido.', 422);
    }

    $nombre = trim((string)($cuerpo['nombre'] ?? ''));
    $email = trim((string)($cuerpo['email'] ?? ''));
    $password = (string)($cuerpo['password'] ?? '');
    $rol = (string)($cuerpo['rol'] ?? '');
    $rolesPermitidosParaAlta = ['admin', 'staff'];

    if ($nombre === '' || mb_strlen($nombre) > 100) {
        responderErrorUsuarios('Nombre inválido.', 422);
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 100) {
        responderErrorUsuarios('Correo inválido.', 422);
    }
    if (mb_strlen($password) < 8) {
        responderErrorUsuarios('La contraseña debe tener al menos 8 caracteres.', 422);
    }
    if (!in_array($rol, $rolesPermitidosParaAlta, true)) {
        responderErrorUsuarios('Rol inválido. Solo se puede crear admin o staff.', 422);
    }

    try {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $conn->prepare(
            "INSERT INTO usuarios_dashboard (nombre, email, password_hash, rol)
             VALUES (:nombre, :email, :password_hash, :rol)"
        );
        $stmt->bindValue(':nombre', $nombre);
        $stmt->bindValue(':email', $email);
        $stmt->bindValue(':password_hash', $hash);
        $stmt->bindValue(':rol', $rol);
        $stmt->execute();
    } catch (PDOException $excepcion) {
        if ((int)$excepcion->errorInfo[1] === 1062) {
            responderErrorUsuarios('Ese correo ya está registrado.', 422);
        }
        error_log('[dashboard_usuarios.php] ' . $excepcion->getMessage());
        responderErrorUsuarios('No se pudo crear el usuario.', 500);
    }

    echo json_encode(["estatus" => "ok"]);
    exit;
}

responderErrorUsuarios('Método no permitido.', 405);
