<?php
declare(strict_types=1);

/**
 * api/auth_dashboard.php — AdryRanch
 * Helper de sesión nativa para el Dashboard administrativo.
 * Tabla: usuarios_dashboard (rol: super_admin | admin | staff).
 */

function iniciarSesionSegura(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

function requireSesionActiva(): array {
    iniciarSesionSegura();
    if (empty($_SESSION['idUsuario'])) {
        http_response_code(401);
        echo json_encode(["estatus" => "error", "mensaje" => "Sesión no válida. Inicia sesión de nuevo."]);
        exit;
    }
    return $_SESSION;
}

function requireRol(array $sesion, array $rolesPermitidos): void {
    if (!in_array($sesion['rol'] ?? '', $rolesPermitidos, true)) {
        http_response_code(403);
        echo json_encode(["estatus" => "error", "mensaje" => "No tienes permiso para esta acción."]);
        exit;
    }
}
