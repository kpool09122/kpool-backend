<?php

declare(strict_types=1);

namespace Source\SiteManagement\User\Domain\ValueObject;

enum Role: string
{
    case ADMIN = 'admin';
    case NONE = 'none';

    public function isAdmin(): bool
    {
        return $this === self::ADMIN;
    }
}
