<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query\ListRelatedWikis;

use Source\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

interface ListRelatedWikisInputPort
{
    public function resourceType(): ResourceType;

    public function translationSetIdentifier(): TranslationSetIdentifier;

    public function principalIdentifier(): PrincipalIdentifier;

    public function accountCategory(): AccountCategory;
}
