<?php

declare(strict_types=1);

namespace Source\Account\Affiliation\Domain\Event;

use DateTimeImmutable;
use Source\Account\Shared\Domain\ValueObject\AccountType;
use Source\Account\Shared\Domain\ValueObject\AffiliationIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

readonly class AffiliationActivated
{
    public function __construct(
        private AffiliationIdentifier $affiliationIdentifier,
        private AccountIdentifier $agencyAccountIdentifier,
        private AccountIdentifier $talentAccountIdentifier,
        private DateTimeImmutable $activatedAt,
        private string $agencyAccountName,
        private string $talentAccountName,
        private AccountType $agencyAccountType,
        private AccountType $talentAccountType,
    ) {
    }

    public function affiliationIdentifier(): AffiliationIdentifier
    {
        return $this->affiliationIdentifier;
    }

    public function agencyAccountIdentifier(): AccountIdentifier
    {
        return $this->agencyAccountIdentifier;
    }

    public function talentAccountIdentifier(): AccountIdentifier
    {
        return $this->talentAccountIdentifier;
    }

    public function activatedAt(): DateTimeImmutable
    {
        return $this->activatedAt;
    }

    public function agencyAccountType(): AccountType
    {
        return $this->agencyAccountType;
    }

    public function talentAccountType(): AccountType
    {
        return $this->talentAccountType;
    }

    public function agencyAccountName(): string
    {
        return $this->agencyAccountName;
    }

    public function talentAccountName(): string
    {
        return $this->talentAccountName;
    }
}
