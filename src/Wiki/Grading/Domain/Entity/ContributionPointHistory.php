<?php

declare(strict_types=1);

namespace Source\Wiki\Grading\Domain\Entity;

use DateTimeImmutable;
use Source\Wiki\Grading\Domain\ValueObject\ContributionPointHistoryIdentifier;
use Source\Wiki\Grading\Domain\ValueObject\ContributorType;
use Source\Wiki\Grading\Domain\ValueObject\Point;
use Source\Wiki\Grading\Domain\ValueObject\YearMonth;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;

readonly class ContributionPointHistory
{
    public function __construct(
        private ContributionPointHistoryIdentifier $id,
        private PrincipalIdentifier                $principalIdentifier,
        private YearMonth                          $yearMonth,
        private Point                              $points,
        private ResourceType                       $resourceType,
        private WikiIdentifier                     $wikiIdentifier,
        private ContributorType                    $contributorType,
        private bool                               $isNewCreation,
        private DateTimeImmutable                  $createdAt,
    ) {
    }

    public function id(): ContributionPointHistoryIdentifier
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

    public function resourceType(): ResourceType
    {
        return $this->resourceType;
    }

    public function wikiIdentifier(): WikiIdentifier
    {
        return $this->wikiIdentifier;
    }

    public function contributorType(): ContributorType
    {
        return $this->contributorType;
    }

    public function isNewCreation(): bool
    {
        return $this->isNewCreation;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
