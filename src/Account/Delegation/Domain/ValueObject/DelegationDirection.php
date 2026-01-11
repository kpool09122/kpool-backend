<?php

declare(strict_types=1);

namespace Source\Account\Delegation\Domain\ValueObject;

enum DelegationDirection: string
{
    case FROM_AGENCY = 'from_agency';  // Agency 側から申請
    case FROM_TALENT = 'from_talent';  // Talent 側から招待

    public function isFromAgency(): bool
    {
        return $this === self::FROM_AGENCY;
    }

    public function isFromTalent(): bool
    {
        return $this === self::FROM_TALENT;
    }
}
