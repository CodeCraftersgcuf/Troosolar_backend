<?php

namespace App\Support;

class UserRole
{
    public const ROLE_USER = 'user';

    public const ROLE_ADMIN = 'admin';

    public const ROLE_SUPER_ADMIN = 'super_admin';

    public static function normalize(?string $role): string
    {
        $normalized = strtolower(trim((string) $role));

        if ($normalized === 'superadmin') {
            return self::ROLE_SUPER_ADMIN;
        }

        return $normalized;
    }

    public static function isAdmin(?string $role): bool
    {
        return in_array(self::normalize($role), [self::ROLE_ADMIN, self::ROLE_SUPER_ADMIN], true);
    }

    public static function isSuperAdmin(?string $role): bool
    {
        return self::normalize($role) === self::ROLE_SUPER_ADMIN;
    }
}
