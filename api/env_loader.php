<?php
declare(strict_types=1);

/**
 * api/env_loader.php — AdryRanch
 * Localizador defensivo multirruta del .env. Centraliza la carga de
 * configuración para que conexion.php, test_conexiones.php y
 * crear_usuarios_iniciales.php compartan la misma lógica — antes cada
 * uno reimplementaba su propio parse_ini_file() con una sola ruta fija,
 * lo que rompía en producción si la estructura del hosting no era
 * idéntica a la local de XAMPP.
 */

function localizarArchivoEnv(): ?string {
    $candidatos = [
        __DIR__ . '/../.env',
        ($_SERVER['DOCUMENT_ROOT'] ?? '') . '/.env',
        dirname(__DIR__, 2) . '/.env',
    ];

    foreach ($candidatos as $ruta) {
        if ($ruta !== '/.env' && is_readable($ruta)) {
            return $ruta;
        }
    }

    return null;
}

function cargarEntornoSeguro(): array {
    $ruta = localizarArchivoEnv();

    if ($ruta === null) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Error crítico de servidor: Configuración no encontrada."]);
        exit;
    }

    $datos = parse_ini_file($ruta, false, INI_SCANNER_RAW);

    if ($datos === false) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Error crítico de servidor: Formato de configuración inválido."]);
        exit;
    }

    return $datos;
}
