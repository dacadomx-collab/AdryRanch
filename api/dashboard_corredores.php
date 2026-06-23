<?php
declare(strict_types=1);

/**
 * api/dashboard_corredores.php — AdryRanch
 * Módulo A del Dashboard: "Palomilla Inscrita" / "Asistencias Aseguradas".
 * GET  → lista todos los registros de registro_corredores.
 * POST → { accion: "confirmar", idRegistro } cambia estatus_pago a 'confirmado'.
 */

require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/auth_dashboard.php';

function responderErrorCorredores(string $mensaje, int $codigoHttp): void {
    http_response_code($codigoHttp);
    echo json_encode(["estatus" => "error", "mensaje" => $mensaje]);
    exit;
}

$sesion = requireSesionActiva();

$db = new Database();
$conn = $db->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $conn->query(
        "SELECT id_registro, nombre_completo, telefono, correo, paquete, se_queda_al_after,
                referencia_pago, estatus_pago, fecha_registro
         FROM registro_corredores
         ORDER BY fecha_registro DESC"
    );
    $filas = $stmt->fetchAll();

    $corredores = array_map(static function (array $fila): array {
        return [
            "idRegistro" => (int)$fila['id_registro'],
            "nombreCompleto" => $fila['nombre_completo'],
            "telefono" => $fila['telefono'],
            "correo" => $fila['correo'],
            "paquete" => $fila['paquete'],
            "seQuedaAlAfter" => $fila['se_queda_al_after'],
            "referenciaPago" => $fila['referencia_pago'],
            "estatusPago" => $fila['estatus_pago'],
            "fechaRegistro" => $fila['fecha_registro'],
        ];
    }, $filas);

    echo json_encode(["estatus" => "ok", "corredores" => $corredores]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cuerpo = json_decode((string)file_get_contents('php://input'), true);
    if (!is_array($cuerpo) || ($cuerpo['accion'] ?? '') !== 'confirmar') {
        responderErrorCorredores('Acción inválida.', 422);
    }

    $idRegistro = (int)($cuerpo['idRegistro'] ?? 0);
    if ($idRegistro <= 0) {
        responderErrorCorredores('idRegistro inválido.', 422);
    }

    try {
        $stmt = $conn->prepare("UPDATE registro_corredores SET estatus_pago = 'confirmado' WHERE id_registro = :id");
        $stmt->bindValue(':id', $idRegistro, PDO::PARAM_INT);
        $stmt->execute();
    } catch (PDOException $excepcion) {
        error_log('[dashboard_corredores.php] ' . $excepcion->getMessage());
        responderErrorCorredores('No se pudo actualizar el estatus.', 500);
    }

    echo json_encode(["estatus" => "ok"]);
    exit;
}

responderErrorCorredores('Método no permitido.', 405);
