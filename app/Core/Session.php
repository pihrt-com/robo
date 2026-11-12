<?php
declare(strict_types=1);

namespace Robo\Core;

final class Session
{
    private const IDLE_TIMEOUT_SECONDS = 28800;
    private const REGENERATE_INTERVAL_SECONDS = 1800;

    public static function start(bool $secureCookie): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_httponly', '1');
        session_name('robo_session');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $secureCookie,
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
        session_start();
    }

    public static function login(int $userId): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $userId;
        $_SESSION['last_activity_at'] = time();
        $_SESSION['regenerated_at'] = time();
    }

    public static function userId(): ?int
    {
        $value = $_SESSION['user_id'] ?? null;
        return is_int($value) ? $value : null;
    }

    public static function maintain(): bool
    {
        if (self::userId() === null) {
            return true;
        }
        $now = time();
        $lastActivity = $_SESSION['last_activity_at'] ?? 0;
        if (!is_int($lastActivity) || $lastActivity < $now - self::IDLE_TIMEOUT_SECONDS) {
            return false;
        }
        $_SESSION['last_activity_at'] = $now;
        $regeneratedAt = $_SESSION['regenerated_at'] ?? 0;
        if (!is_int($regeneratedAt) || $regeneratedAt < $now - self::REGENERATE_INTERVAL_SECONDS) {
            session_regenerate_id(true);
            $_SESSION['regenerated_at'] = $now;
        }
        return true;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], '', (bool) $params['secure'], (bool) $params['httponly']);
        }
        session_destroy();
    }
}
