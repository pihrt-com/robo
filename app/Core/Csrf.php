<?php
declare(strict_types=1);

namespace Robo\Core;

use RuntimeException;

final class Csrf
{
    private const KEY = '_robo_csrf';

    public static function token(): string
    {
        self::ensureSession();
        if (!isset($_SESSION[self::KEY])) {
            $_SESSION[self::KEY] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::KEY];
    }

    public static function verify(?string $token): void
    {
        self::ensureSession();
        $current = $_SESSION[self::KEY] ?? '';
        if (!is_string($token) || !is_string($current) || !hash_equals($current, $token)) {
            throw new RuntimeException('Požadavek nelze ověřit. Obnovte stránku a zkuste akci znovu.');
        }
    }

    private static function ensureSession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            throw new RuntimeException('Relace není spuštěna.');
        }
    }
}
