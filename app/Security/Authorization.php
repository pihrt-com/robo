<?php
declare(strict_types=1);

namespace Robo\Security;

use RuntimeException;

final class Authorization
{
    /** @param list<string> $permissions */
    public static function allows(string $roleCode, array $permissions, string $required): bool
    {
        return $roleCode === 'administrator' || in_array('*', $permissions, true) || in_array($required, $permissions, true);
    }

    /** @param list<string> $permissions */
    public static function require(string $roleCode, array $permissions, string $required): void
    {
        if (!self::allows($roleCode, $permissions, $required)) {
            throw new RuntimeException('Pro tuto akci nemáte oprávnění.');
        }
    }
}
