<?php
declare(strict_types=1);

$__mysqli = null;

function db(): mysqli {
    global $__mysqli;
    if ($__mysqli instanceof mysqli) return $__mysqli;

    $__mysqli = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($__mysqli->connect_errno) {
        error_log('DB connect error: ' . $__mysqli->connect_error);
        http_response_code(500);
        exit('Error de conexión a base de datos');
    }
    $__mysqli->set_charset('utf8mb4');
    return $__mysqli;
}