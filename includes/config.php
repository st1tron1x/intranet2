<?php
declare(strict_types=1);

// Cookies/seguridad de sesión
ini_set('session.cookie_httponly', '1');
ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_samesite', 'Lax');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ajusta a tu URL local (sin /public)
const APP_NAME = 'Intranet Correagro';
const APP_URL  = 'http://localhost/intranet';

// Config DB (ajusta credenciales)
const DB_HOST = '127.0.0.1';
const DB_USER = 'root';
const DB_PASS = '';
const DB_NAME = 'intranet';

// Carga helpers/db/auth
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';