<?php
declare(strict_types=1);

function e(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
function url(string $path=''): string { return rtrim(APP_URL,'/') . '/' . ltrim($path,'/'); }
function redirect(string $to): void { header('Location: '.$to); exit; }

// Flash messages en sesión
function flash(string $key, ?string $msg=null): ?string {
    if ($msg === null) { $m = $_SESSION['flash'][$key] ?? null; unset($_SESSION['flash'][$key]); return $m; }
    $_SESSION['flash'][$key] = $msg; return null;
}

// CSRF básico
function csrf_token(): string {
    if (empty($_SESSION['_csrf'])) $_SESSION['_csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['_csrf'];
}
function csrf_verify(): bool {
    return isset($_POST['_csrf'], $_SESSION['_csrf']) && hash_equals($_SESSION['_csrf'], $_SESSION['_csrf'] = $_POST['_csrf']);
}
