<?php

declare(strict_types=1);

namespace Source\Account\Delegation\Domain\ValueObject;

enum DelegationStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REVOKED = 'revoked';

    public function isPending(): bool
    {
        return $this === self::PENDING;
    }

    public function isApproved(): bool
    {
        return $this === self::APPROVED;
    }

    public function isRevoked(): bool
    {
        return $this === self::REVOKED;
    }
}
