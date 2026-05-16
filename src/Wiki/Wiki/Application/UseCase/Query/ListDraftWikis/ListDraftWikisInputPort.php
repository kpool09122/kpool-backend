<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query\ListDraftWikis;

use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

interface ListDraftWikisInputPort
{
    public function perPage(): int;

    public function translationSetIdentifier(): ?TranslationSetIdentifier;

    public function status(): ApprovalStatus;

    public function resourceType(): ?ResourceType;
}
