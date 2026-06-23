<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

// Cargar variables del .env de forma segura (escaneo multirruta, ver env_loader.php)
require_once __DIR__ . '/env_loader.php';
$env = cargarEntornoSeguro();

// Validar Token de Seguridad para evitar ejecuciones externas maliciosas (Mandamiento 14)
$headers = getallheaders();
$authToken = (string)($headers['Authorization'] ?? $_GET['token'] ?? '');
if (!hash_equals((string)($env['TEST_TOKEN'] ?? ''), $authToken)) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Acceso perimetral no autorizado.']);
    exit;
}

$reporte = [
    'status' => 'success',
    'timestamp' => date('Y-m-d H:i:s'),
    'fases_auditoria' => []
];

// ─────────────────────────────────────────────────────────────────────
// FASE A: Auditoría y Ciclo de Vida de Base de Datos (CRUD)
// ─────────────────────────────────────────────────────────────────────
try {
    $dsn = "mysql:host={$env['DB_HOST']};dbname={$env['DB_NAME']};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false, // Desactivación nativa de emulación (Anti-SQLi)
        PDO::ATTR_TIMEOUT            => 5, // Evitar bloqueo prolongado si el host remoto no responde
    ];
    
    $pdo = new PDO($dsn, $env['DB_USER'], $env['DB_PASS'], $options);
    $reporte['fases_auditoria']['database_conexion'] = 'OK — Conectado a MariaDB/MySQL exitosamente.';

    // 1. CREATE (Insertar corredor de prueba de la palomilla)
    $dummyFolio = 'LUNA-' . date('ymd') . '-TEST99';
    $sqlInsert = "INSERT INTO registro_corredores 
        (nombre_completo, telefono, correo, contacto_emergencia, paquete, se_queda_al_after, estatus_pago, referencia_pago, fecha_vigencia_pago) 
        VALUES (:nombre, :tel, :correo, :emergencia, :paquete, :after, :estatus, :ref, :vigencia)";
    
    $stmt = $pdo->prepare($sqlInsert);
    $stmt->execute([
        ':nombre' => 'Corredor de Prueba Ecosistema',
        ':tel' => '6121234567',
        ':correo' => 'test_runner@adryranch.com',
        ':emergencia' => 'Contacto Emergencia Test 6127654321',
        ':paquete' => 'individual',
        ':after' => 'si',
        ':estatus' => 'pre_apartado',
        ':ref' => $dummyFolio,
        ':vigencia' => date('Y-m-d H:i:s', strtotime('+48 hours'))
    ]);
    $idInsertado = $pdo->lastInsertId();
    $reporte['fases_auditoria']['crud_create'] = "OK — Registro insertado exitosamente con ID: {$idInsertado} y Folio: {$dummyFolio}.";

    // 2. READ (Verificar existencia espejo exacta)
    $sqlSelect = "SELECT * FROM registro_corredores WHERE id_registro = :id";
    $stmt = $pdo->prepare($sqlSelect);
    $stmt->execute([':id' => $idInsertado]);
    $registro = $stmt->fetch();
    
    if ($registro) {
        $reporte['fases_auditoria']['crud_read'] = "OK — Corredor recuperado de forma exacta desde MariaDB.";
    } else {
        throw new Exception("Error de consistencia: No se pudo leer el registro insertado.");
    }

    // 3. UPDATE (Simular confirmación de aportación de corredor)
    $sqlUpdate = "UPDATE registro_corredores SET estatus_pago = 'confirmado' WHERE id_registro = :id";
    $stmt = $pdo->prepare($sqlUpdate);
    $stmt->execute([':id' => $idInsertado]);
    $reporte['fases_auditoria']['crud_update'] = "OK — Estatus de aportación modificado a 'confirmado' de forma segura.";

    // 4. DELETE (Limpiar rastro de prueba de la bitácora)
    $sqlDelete = "DELETE FROM registro_corredores WHERE id_registro = :id";
    $stmt = $pdo->prepare($sqlDelete);
    $stmt->execute([':id' => $idInsertado]);
    $reporte['fases_auditoria']['crud_delete'] = "OK — Registro de prueba eliminado. Base de datos limpia y en producción.";

} catch (Exception $e) {
    $reporte['status'] = 'error';
    $reporte['fases_auditoria']['database_error'] = 'CRÍTICO — Fallo en el motor de persistencia: ' . $e->getMessage();
}

// ─────────────────────────────────────────────────────────────────────
// FASE B: Auditoría de Conexión y Apertura de Sockets SMTP
// ─────────────────────────────────────────────────────────────────────
// Prueba de bajo nivel antes de instanciar librerías pesadas (PHPMailer)
$smtpSocket = @fsockopen($env['SMTP_HOST'], (int)$env['SMTP_PORT'], $errno, $errstr, 5);
if ($smtpSocket) {
    $reporte['fases_auditoria']['smtp_socket'] = "OK — Puerto {$env['SMTP_PORT']} abierto y respondiendo en el servidor remoto.";
    fclose($smtpSocket);
} else {
    $reporte['status'] = 'error';
    $reporte['fases_auditoria']['smtp_socket_error'] = "CRÍTICO — No se pudo establecer conexión al socket de correo: {$errstr} ({$errno})";
}

// ─────────────────────────────────────────────────────────────────────
// FASE C: Auditoría de Conexión y Apertura de Socket FTP
// ─────────────────────────────────────────────────────────────────────
$ftpSocket = @fsockopen($env['FTP_HOST'], (int)$env['FTP_PORT'], $errno, $errstr, 5);
if ($ftpSocket) {
    $reporte['fases_auditoria']['ftp_socket'] = "OK — Puerto {$env['FTP_PORT']} abierto y respondiendo en el servidor remoto.";
    fclose($ftpSocket);
} else {
    $reporte['status'] = 'error';
    $reporte['fases_auditoria']['ftp_socket_error'] = "CRÍTICO — No se pudo establecer conexión al socket FTP: {$errstr} ({$errno})";
}

echo json_encode($reporte, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);