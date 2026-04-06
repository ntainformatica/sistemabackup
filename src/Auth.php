<?php

declare(strict_types=1);

/**
 * Autenticação v1 NOC — sessão PHP + cookie.
 * Utilizadores: tabela noc_users (username comparado sem distinção de maiúsculas).
 */
final class Auth
{
    private const SESSION_USER_ID = 'noc_uid';
    private const SESSION_USERNAME = 'noc_username';

    public static function initSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_name('NOCSESSID');
        session_start();
    }

    public static function isAuthenticated(): bool
    {
        return isset($_SESSION[self::SESSION_USER_ID])
            && is_int($_SESSION[self::SESSION_USER_ID])
            && $_SESSION[self::SESSION_USER_ID] > 0;
    }

    public static function currentUserId(): ?int
    {
        if (!self::isAuthenticated()) {
            return null;
        }

        return $_SESSION[self::SESSION_USER_ID];
    }

    public static function currentUsername(): ?string
    {
        if (!isset($_SESSION[self::SESSION_USERNAME])) {
            return null;
        }

        return (string) $_SESSION[self::SESSION_USERNAME];
    }

    /**
     * URL interna segura para redirecionamento pós-login (apenas index.php no mesmo site).
     */
    public static function safeReturnUrl(?string $return): string
    {
        if ($return === null) {
            return 'index.php?route=board';
        }
        $return = trim($return);
        if ($return === '') {
            return 'index.php?route=board';
        }
        if (str_contains($return, '://') || str_starts_with($return, '//')) {
            return 'index.php?route=board';
        }
        if (!preg_match('#^index\.php#', $return)) {
            return 'index.php?route=board';
        }

        return $return;
    }

    public static function requireAuthHtml(): void
    {
        if (self::isAuthenticated()) {
            return;
        }
        $qs = $_SERVER['QUERY_STRING'] ?? '';
        $ret = $qs !== '' ? 'index.php?' . $qs : 'index.php?route=board';
        $url = 'index.php?route=login&return=' . rawurlencode($ret);
        header('Location: ' . $url, true, 302);
        exit;
    }

    public static function requireAuthApi(): void
    {
        if (self::isAuthenticated()) {
            return;
        }
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => 'unauthorized'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * @return bool true se login bem-sucedido
     */
    public static function attemptLogin(PDO $pdo, string $username, string $password): bool
    {
        $username = trim($username);
        if ($username === '' || $password === '') {
            return false;
        }

        $sql = '
            SELECT id, username, password_hash
            FROM noc_users
            WHERE lower(trim(username)) = lower(trim(:u))
              AND is_active = TRUE
            LIMIT 2
        ';
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['u' => $username]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (count($rows) === 0) {
            return false;
        }

        $row = $rows[0];
        if (!password_verify($password, (string) $row['password_hash'])) {
            return false;
        }

        session_regenerate_id(true);
        $_SESSION[self::SESSION_USER_ID] = (int) $row['id'];
        $_SESSION[self::SESSION_USERNAME] = (string) $row['username'];

        $upd = $pdo->prepare('UPDATE noc_users SET last_login_at = now(), updated_at = now() WHERE id = :id');
        $upd->execute(['id' => (int) $row['id']]);

        return true;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $p['path'],
                $p['domain'],
                $p['secure'],
                $p['httponly']
            );
        }
        session_destroy();
    }
}
