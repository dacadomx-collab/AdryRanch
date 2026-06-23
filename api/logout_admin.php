<?php
declare(strict_types=1);

require_once __DIR__ . '/auth_dashboard.php';

header("Content-Type: application/json; charset=UTF-8");

iniciarSesionSegura();
$_SESSION = [];
session_destroy();

echo json_encode(["estatus" => "ok"]);
