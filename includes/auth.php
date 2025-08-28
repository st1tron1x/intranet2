<?php
declare(strict_types=1);

final class Auth {
    // Ajustado a tu tabla `usuarios` (usuario, clave, nombre_completo, admin, activo)
    public static function attempt(string $usuario, string $clave): bool {
        $usuario = trim($usuario);

        $stmt = db()->prepare(
            'SELECT id, usuario, clave, nombre_completo, admin 
             FROM usuarios 
             WHERE usuario = ? AND activo = 1 
             LIMIT 1'
        );
        $stmt->bind_param('s', $usuario);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($row = $res->fetch_assoc()) {
            // Si tu BD ya está 100% con password_hash, basta con password_verify:
            $ok = password_verify($clave, $row['clave']);
            // Si necesitas compatibilidad con claves antiguas en texto plano, descomenta:
            // $ok = $ok || ($row['clave'] === $clave);

            if ($ok) {
                session_regenerate_id(true);
                $_SESSION['user'] = [
                    'id'      => (int)$row['id'],
                    'usuario' => $row['usuario'],
                    'nombre'  => $row['nombre_completo'],
                    'admin'   => (int)$row['admin'], // 1 admin, 0 cliente
                    'last'    => time(),
                ];
                return true;
            }
        }
        return false;
    }

    public static function check(): bool {
        return !empty($_SESSION['user']['id']);
    }

    public static function user(): ?array {
        return self::check() ? $_SESSION['user'] : null;
    }

    public static function isAdmin(): bool {
        return self::check() && ((int)$_SESSION['user']['admin'] === 1);
    }

    public static function requireRole(string $role): void {
        if (!self::check()) {
            flash('error', 'Debes iniciar sesión');
            redirect(url('index.php'));
        }
        if ($role === 'admin' && !self::isAdmin()) {
            flash('error', 'No autorizado');
            redirect(url('cliente/dashboard.php'));
        }
        if ($role === 'cliente' && self::isAdmin()) {
            // Un admin puede entrar a cliente si quieres; si no, redirige:
            // redirect(url('admin/dashboard.php'));
        }
    }

    public static function logout(): void {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) session_destroy();
    }
}
