<?php

declare(strict_types=1);

namespace Source\Wiki\Grading\Domain\Entity;

use DateTimeImmutable;
use Source\Wiki\Grading\Domain\ValueObject\DemotionWarningIdentifier;
use Source\Wiki\Grading\Domain\ValueObject\WarningCount;
use Source\Wiki\Grading\Domain\ValueObject\YearMonth;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

class DemotionWarning
{
    public function __construct(
        private readonly DemotionWarningIdentifier $id,
        private readonly PrincipalIdentifier $principalIdentifier,
        private WarningCount $warningCount,
        private YearMonth $lastWarningMonth,
        private readonly DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
    ) {
    }

    public function id(): DemotionWarningIdentifier
    {
        return $this->id;
    }

    public function principalIdentifier(): PrincipalIdentifier
    {
        return $this->principalIdentifier;
    }

    public function warningCount(): WarningCount
    {
        return $this->warningCount;
    }

    public function incrementWarningCount(): void
    {
        $this->warningCount = $this->warningCount->increment();
    }

    public function resetWarningCount(): void
    {
        $this->warningCount = new WarningCount(0);
    }

    public function lastWarningMonth(): YearMonth
    {
        return $this->lastWarningMonth;
    }

    public function setLastWarningMonth(YearMonth $lastWarningMonth): void
    {
        $this->lastWarningMonth = $lastWarningMonth;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTimeImmutable $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }
}
