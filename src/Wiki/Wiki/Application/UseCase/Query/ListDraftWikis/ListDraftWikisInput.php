<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query\ListDraftWikis;

use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

readonly class ListDraftWikisInput implements ListDraftWikisInputPort
{
    public function __construct(
        /** @var ApprovalStatus[] */
        private array $statuses,
        private PrincipalIdentifier $principalIdentifier,
        private ?TranslationSetIdentifier $translationSetIdentifier = null,
        private ?ResourceType $resourceType = null,
        private ?int $perPage = null,
    ) {
    }

    public function perPage(): int
    {
        return $this->perPage ?? 10;
    }

    public function translationSetIdentifier(): ?TranslationSetIdentifier
    {
        return $this->translationSetIdentifier;
    }

    /** @return ApprovalStatus[] */
    public function statuses(): array
    {
        return $this->statuses;
    }

    public function resourceType(): ?ResourceType
    {
        return $this->resourceType;
    }

    public function principalIdentifier(): PrincipalIdentifier
    {
        return $this->principalIdentifier;
    }
}
