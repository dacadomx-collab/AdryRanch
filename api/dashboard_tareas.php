<?php
declare(strict_types=1);

/**
 * api/dashboard_tareas.php — AdryRanch
 * Módulo B del Dashboard: Task Synchronizer.
 * GET  → lista tareas_evento con el nombre del responsable asignado.
 * POST accion=crear              → { titulo, descripcion, asignadoA, fechaLimite }
 * POST accion=actualizar_estatus → { idTarea, estatus }
 */

require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/auth_dashboard.php';

function responderErrorTareas(string $mensaje, int $codigoHttp): void {
    http_response_code($codigoHttp);
    echo json_encode(["estatus" => "error", "mensaje" => $mensaje]);
    exit;
}

$sesion = requireSesionActiva();

$db = new Database();
$conn = $db->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['responsables'])) {
    $stmt = $conn->query("SELECT id_usuario, nombre FROM usuarios_dashboard ORDER BY nombre ASC");
    $responsables = array_map(static function (array $fila): array {
        return ["idUsuario" => (int)$fila['id_usuario'], "nombre" => $fila['nombre']];
    }, $stmt->fetchAll());

    echo json_encode(["estatus" => "ok", "responsables" => $responsables]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $conn->query(
        "SELECT t.id_tarea, t.titulo, t.descripcion, t.asignado_a, u.nombre AS asignado_nombre,
                t.estatus, t.fecha_limite, t.creado_at
         FROM tareas_evento t
         LEFT JOIN usuarios_dashboard u ON u.id_usuario = t.asignado_a
         ORDER BY t.creado_at DESC"
    );
    $filas = $stmt->fetchAll();

    $tareas = array_map(static function (array $fila): array {
        return [
            "idTarea" => (int)$fila['id_tarea'],
            "titulo" => $fila['titulo'],
            "descripcion" => $fila['descripcion'],
            "asignadoA" => $fila['asignado_a'] !== null ? (int)$fila['asignado_a'] : null,
            "asignadoNombre" => $fila['asignado_nombre'],
            "estatus" => $fila['estatus'],
            "fechaLimite" => $fila['fecha_limite'],
        ];
    }, $filas);

    echo json_encode(["estatus" => "ok", "tareas" => $tareas]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cuerpo = json_decode((string)file_get_contents('php://input'), true);
    if (!is_array($cuerpo)) {
        responderErrorTareas('Cuerpo de la solicitud inválido.', 422);
    }

    $accion = (string)($cuerpo['accion'] ?? '');

    if ($accion === 'crear') {
        $titulo = trim((string)($cuerpo['titulo'] ?? ''));
        $descripcion = trim((string)($cuerpo['descripcion'] ?? ''));
        $asignadoA = isset($cuerpo['asignadoA']) && $cuerpo['asignadoA'] !== '' ? (int)$cuerpo['asignadoA'] : null;
        $fechaLimite = trim((string)($cuerpo['fechaLimite'] ?? ''));

        if ($titulo === '' || mb_strlen($titulo) > 150) {
            responderErrorTareas('Título de tarea inválido.', 422);
        }

        try {
            $stmt = $conn->prepare(
                "INSERT INTO tareas_evento (titulo, descripcion, asignado_a, estatus, fecha_limite)
                 VALUES (:titulo, :descripcion, :asignado_a, 'pendiente', :fecha_limite)"
            );
            $stmt->bindValue(':titulo', $titulo);
            $stmt->bindValue(':descripcion', $descripcion !== '' ? $descripcion : null);
            $stmt->bindValue(':asignado_a', $asignadoA, $asignadoA === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $stmt->bindValue(':fecha_limite', $fechaLimite !== '' ? $fechaLimite : null);
            $stmt->execute();
        } catch (PDOException $excepcion) {
            error_log('[dashboard_tareas.php][crear] ' . $excepcion->getMessage());
            responderErrorTareas('No se pudo crear la tarea.', 500);
        }

        echo json_encode(["estatus" => "ok"]);
        exit;
    }

    if ($accion === 'actualizar_estatus') {
        $idTarea = (int)($cuerpo['idTarea'] ?? 0);
        $estatus = (string)($cuerpo['estatus'] ?? '');
        $estatusValidos = ['pendiente', 'en_progreso', 'completado'];

        if ($idTarea <= 0 || !in_array($estatus, $estatusValidos, true)) {
            responderErrorTareas('Datos de actualización inválidos.', 422);
        }

        try {
            $stmt = $conn->prepare("UPDATE tareas_evento SET estatus = :estatus WHERE id_tarea = :id");
            $stmt->bindValue(':estatus', $estatus);
            $stmt->bindValue(':id', $idTarea, PDO::PARAM_INT);
            $stmt->execute();
        } catch (PDOException $excepcion) {
            error_log('[dashboard_tareas.php][actualizar_estatus] ' . $excepcion->getMessage());
            responderErrorTareas('No se pudo actualizar la tarea.', 500);
        }

        echo json_encode(["estatus" => "ok"]);
        exit;
    }

    responderErrorTareas('Acción inválida.', 422);
}

responderErrorTareas('Método no permitido.', 405);
