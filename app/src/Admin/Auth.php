<?php
declare(strict_types=1);

namespace Adepc\Admin;

use Adepc\Db;
use Adepc\Http;

/**
 * Admin sessions: hardened cookie, ID regenerated at login, 2-hour idle timeout,
 * CSRF token per session, and login throttling (per IP and per account).
 */
final class Auth
{
    public const IDLE_SECONDS = 7200;
    public const MAX_FAILURES = 5;
    public const WINDOW_MINUTES = 15;

    private static ?array $user = null;

    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        session_name('adepc_admin');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/admin',
            'secure' => Http::isHttps(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        session_start();
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }
    }

    public static function user(): ?array
    {
        if (self::$user !== null) {
            return self::$user;
        }
        $id = $_SESSION['admin_id'] ?? null;
        if (!$id) {
            return null;
        }
        if (time() - (int) ($_SESSION['seen'] ?? 0) > self::IDLE_SECONDS) {
            self::logout();
            return null;
        }
        $_SESSION['seen'] = time();
        self::$user = Db::one('SELECT id, email, name, role, ui_locale FROM admins WHERE id = ?', [$id]);
        if (!self::$user) {
            self::logout();
        }
        return self::$user;
    }

    public static function isAdmin(): bool
    {
        return (self::user()['role'] ?? '') === 'admin';
    }

    public static function csrf(): string
    {
        return (string) ($_SESSION['csrf'] ?? '');
    }

    public static function checkCsrf(): bool
    {
        $sent = (string) ($_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        return $sent !== '' && hash_equals(self::csrf(), $sent);
    }

    /** @return string|null error key, null on success */
    public static function attempt(string $email, string $password): ?string
    {
        $email = strtolower(trim($email));
        $ip = Http::ip();
        $since = gmdate('Y-m-d H:i:s', time() - self::WINDOW_MINUTES * 60);
        $recentIp = (int) Db::value('SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND attempted_at > ?', [$ip, $since]);
        $recentEmail = (int) Db::value('SELECT COUNT(*) FROM login_attempts WHERE email = ? AND attempted_at > ?', [$email, $since]);
        if ($recentIp >= self::MAX_FAILURES * 4 || $recentEmail >= self::MAX_FAILURES) {
            return 'admin.login.locked';
        }
        $row = Db::one('SELECT * FROM admins WHERE email = ?', [$email]);
        $now = gmdate('Y-m-d H:i:s');
        if ($row && $row['locked_until'] && $row['locked_until'] > $now) {
            return 'admin.login.locked';
        }
        // Verify against a dummy hash when the account doesn't exist (same timing).
        $hash = $row['password_hash'] ?? '$2y$12$YOORev4oqt.CIH2IQ3D11uW7CVNsmSnGCiLZI/bUelOReXnEY3jxm';
        if (!$row || !password_verify($password, $hash)) {
            Db::insert('login_attempts', ['ip' => $ip, 'email' => $email, 'attempted_at' => $now]);
            if ($row) {
                $failures = (int) $row['failed_attempts'] + 1;
                Db::update('admins', [
                    'failed_attempts' => $failures,
                    'locked_until' => $failures >= self::MAX_FAILURES ? gmdate('Y-m-d H:i:s', time() + self::WINDOW_MINUTES * 60) : null,
                ], 'id = ?', [$row['id']]);
            }
            return 'admin.login.invalid';
        }
        if (password_needs_rehash($row['password_hash'], PASSWORD_DEFAULT)) {
            Db::update('admins', ['password_hash' => password_hash($password, PASSWORD_DEFAULT)], 'id = ?', [$row['id']]);
        }
        Db::update('admins', ['failed_attempts' => 0, 'locked_until' => null, 'last_login_at' => $now], 'id = ?', [$row['id']]);
        Db::exec('DELETE FROM login_attempts WHERE email = ?', [$email]);
        session_regenerate_id(true);
        $_SESSION['admin_id'] = (int) $row['id'];
        $_SESSION['seen'] = time();
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
        self::$user = null;
        return null;
    }

    public static function logout(): void
    {
        self::$user = null;
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }
    }

    /** Creates a one-hour reset token; returns the raw token (only its hash is stored). */
    public static function createResetToken(int $adminId): string
    {
        $token = bin2hex(random_bytes(32));
        Db::update('admins', [
            'reset_token_hash' => hash('sha256', $token),
            'reset_expires' => gmdate('Y-m-d H:i:s', time() + 3600),
        ], 'id = ?', [$adminId]);
        return $token;
    }

    public static function adminForToken(string $token): ?array
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            return null;
        }
        return Db::one('SELECT * FROM admins WHERE reset_token_hash = ? AND reset_expires > ?', [hash('sha256', $token), gmdate('Y-m-d H:i:s')]);
    }

    public static function validPassword(string $password): bool
    {
        return mb_strlen($password, 'UTF-8') >= 10;
    }
}
