<?php

declare(strict_types=1);

namespace Source\Wiki\Grading\Domain\Entity;

use DateTimeImmutable;
use Source\Wiki\Grading\Domain\ValueObject\PromotionHistoryIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

readonly class PromotionHistory
{
    public function __construct(
        private PromotionHistoryIdentifier $id,
        private PrincipalIdentifier        $principalIdentifier,
        private string                     $fromRole,
        private string                     $toRole,
        private ?string                    $reason,
        private DateTimeImmutable          $processedAt,
    ) {
    }

    public function id(): PromotionHistoryIdentifier
    {
        return $this->id;
    }

    public function principalIdentifier(): PrincipalIdentifier
    {
        return $this->principalIdentifier;
    }

    public function fromRole(): string
    {
        return $this->fromRole;
    }

    public function toRole(): string
    {
        return $this->toRole;
    }

    public function reason(): ?string
    {
        return $this->reason;
    }

    public function processedAt(): DateTimeImmutable
    {
        return $this->processedAt;
    }
}
