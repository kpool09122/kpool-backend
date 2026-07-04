<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query\ListMyDraftWikis;

use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

interface ListMyDraftWikisInputPort
{
    public function perPage(): int;

    public function translationSetIdentifier(): ?TranslationSetIdentifier;

    /** @return ApprovalStatus[] */
    public function statuses(): array;

    public function resourceType(): ?ResourceType;

    public function editorIdentifier(): PrincipalIdentifier;
}
