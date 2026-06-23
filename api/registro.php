<?php
declare(strict_types=1);

/**
 * api/registro.php — AdryRanch
 * Recibe el formulario de inscripción de "Trail Nocturno La Paz" y lo
 * guarda en `registro_corredores`. Contrato: knowledge/03_CONTRATOS_API_Y_RUTAS.md §0.
 */

require_once __DIR__ . '/conexion.php';

function responderError(string $mensaje, int $codigoHttp): void {
    http_response_code($codigoHttp);
    echo json_encode(["estatus" => "error", "mensaje" => $mensaje]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderError('Método no permitido.', 405);
}

$cuerpo = json_decode((string)file_get_contents('php://input'), true);
if (!is_array($cuerpo)) {
    responderError('Cuerpo de la solicitud inválido.', 422);
}

$nombreCompleto = trim((string)($cuerpo['nombreCompleto'] ?? ''));
$telefono = trim((string)($cuerpo['telefono'] ?? ''));
$correo = trim((string)($cuerpo['correo'] ?? ''));
$contactoEmergencia = trim((string)($cuerpo['contactoEmergencia'] ?? ''));
$paquete = trim((string)($cuerpo['paquete'] ?? ''));
$seQuedaAlAfter = trim((string)($cuerpo['seQuedaAlAfter'] ?? ''));

$paquetesValidos = ['individual', 'pareja', 'equipo'];
$opcionesAfterValidas = ['si', 'no'];

if ($nombreCompleto === '' || mb_strlen($nombreCompleto) > 150) {
    responderError('Nombre completo inválido.', 422);
}
if ($telefono === '' || mb_strlen($telefono) > 20 || !preg_match('/^[0-9+\-\s()]{7,20}$/', $telefono)) {
    responderError('Teléfono inválido.', 422);
}
if ($correo === '' || mb_strlen($correo) > 150 || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    responderError('Correo electrónico inválido.', 422);
}
if ($contactoEmergencia === '' || mb_strlen($contactoEmergencia) > 150) {
    responderError('Contacto de emergencia inválido.', 422);
}
if (!in_array($paquete, $paquetesValidos, true)) {
    responderError('Paquete inválido.', 422);
}
if (!in_array($seQuedaAlAfter, $opcionesAfterValidas, true)) {
    responderError('Respuesta de after inválida.', 422);
}

$referenciaPago = 'LUNA-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
$vigenciaHoras = 48;

$db = new Database();
$conn = $db->getConnection();

try {
    $sql = "INSERT INTO registro_corredores
                (nombre_completo, telefono, correo, contacto_emergencia, paquete, se_queda_al_after,
                 estatus_pago, referencia_pago, fecha_registro, fecha_vigencia_pago)
            VALUES
                (:nombre_completo, :telefono, :correo, :contacto_emergencia, :paquete, :se_queda_al_after,
                 'pre_apartado', :referencia_pago, NOW(), DATE_ADD(NOW(), INTERVAL :vigencia_horas HOUR))";

    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':nombre_completo', $nombreCompleto, PDO::PARAM_STR);
    $stmt->bindValue(':telefono', $telefono, PDO::PARAM_STR);
    $stmt->bindValue(':correo', $correo, PDO::PARAM_STR);
    $stmt->bindValue(':contacto_emergencia', $contactoEmergencia, PDO::PARAM_STR);
    $stmt->bindValue(':paquete', $paquete, PDO::PARAM_STR);
    $stmt->bindValue(':se_queda_al_after', $seQuedaAlAfter, PDO::PARAM_STR);
    $stmt->bindValue(':referencia_pago', $referenciaPago, PDO::PARAM_STR);
    $stmt->bindValue(':vigencia_horas', $vigenciaHoras, PDO::PARAM_INT);
    $stmt->execute();
} catch (PDOException $excepcion) {
    error_log('[registro.php] ' . $excepcion->getMessage());
    responderError('No pudimos guardar tu registro. Intenta de nuevo en unos minutos.', 500);
}

http_response_code(201);
echo json_encode([
    "estatus" => "pre_apartado",
    "referenciaPago" => $referenciaPago,
    "vigenciaHoras" => $vigenciaHoras,
    "nombreCompleto" => $nombreCompleto,
]);
