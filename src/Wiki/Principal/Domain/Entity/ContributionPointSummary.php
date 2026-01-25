<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Domain\Entity;

use DateTimeImmutable;
use Source\Wiki\Principal\Domain\ValueObject\ContributionPointSummaryIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\Point;
use Source\Wiki\Principal\Domain\ValueObject\YearMonth;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

class ContributionPointSummary
{
    public function __construct(
        private readonly ContributionPointSummaryIdentifier $id,
        private readonly PrincipalIdentifier $principalIdentifier,
        private readonly YearMonth $yearMonth,
        private Point $points,
        private readonly DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
    ) {
    }

    public function id(): ContributionPointSummaryIdentifier
    {
        return $this->id;
    }

    public function principalIdentifier(): PrincipalIdentifier
    {
        return $this->principalIdentifier;
    }

    public function yearMonth(): YearMonth
    {
        return $this->yearMonth;
    }

    public function points(): Point
    {
        return $this->points;
    }

    public function setPoints(Point $points): void
    {
        $this->points = $points;
    }

    public function addPoints(Point $points): void
    {
        $this->points = $this->points->add($points);
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
