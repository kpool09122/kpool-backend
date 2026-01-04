<?php

declare(strict_types=1);

namespace Source\Account\Domain\ValueObject;

enum AffiliationStatus: string
{
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case TERMINATED = 'terminated';

    public function isPending(): bool
    {
        return $this === self::PENDING;
    }

    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }

    public function isTerminated(): bool
    {
        return $this === self::TERMINATED;
    }
}
