<?php

declare(strict_types=1);

namespace Source\Account\Account\Domain\ValueObject;

enum AccountTypeChangeRequestStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    public function isPending(): bool
    {
        return $this === self::PENDING;
    }

    public function canTransitionTo(self $status): bool
    {
        return $this === self::PENDING && $status !== self::PENDING;
    }
}
