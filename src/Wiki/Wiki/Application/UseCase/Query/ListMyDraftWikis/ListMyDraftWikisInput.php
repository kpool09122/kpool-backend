<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query\ListMyDraftWikis;

use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

readonly class ListMyDraftWikisInput implements ListMyDraftWikisInputPort
{
    public function __construct(
        private ApprovalStatus $status,
        private PrincipalIdentifier $editorIdentifier,
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

    public function status(): ApprovalStatus
    {
        return $this->status;
    }

    public function resourceType(): ?ResourceType
    {
        return $this->resourceType;
    }

    public function editorIdentifier(): PrincipalIdentifier
    {
        return $this->editorIdentifier;
    }
}
