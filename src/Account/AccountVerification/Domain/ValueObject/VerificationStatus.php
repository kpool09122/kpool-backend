<?php

declare(strict_types=1);

namespace Source\Account\AccountVerification\Domain\ValueObject;

enum VerificationStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    public function isPending(): bool
    {
        return $this === self::PENDING;
    }

    public function isApproved(): bool
    {
        return $this === self::APPROVED;
    }

    public function isRejected(): bool
    {
        return $this === self::REJECTED;
    }

    public function canTransitionTo(VerificationStatus $newStatus): bool
    {
        return match ($this) {
            self::PENDING => in_array($newStatus, [self::APPROVED, self::REJECTED], true),
            self::APPROVED, self::REJECTED => false,
        };
    }
}
