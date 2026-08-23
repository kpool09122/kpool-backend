<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query\ListRelatedWikis;

use InvalidArgumentException;
use Source\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

readonly class ListRelatedWikisInput implements ListRelatedWikisInputPort
{
    public function __construct(
        private ResourceType $resourceType,
        private TranslationSetIdentifier $translationSetIdentifier,
        private PrincipalIdentifier $principalIdentifier,
        private AccountCategory $accountCategory,
    ) {
        if (! in_array($resourceType, [ResourceType::AGENCY, ResourceType::TALENT], true)) {
            throw new InvalidArgumentException('Source resource type must be agency or talent.');
        }
    }

    public function resourceType(): ResourceType
    {
        return $this->resourceType;
    }

    public function translationSetIdentifier(): TranslationSetIdentifier
    {
        return $this->translationSetIdentifier;
    }

    public function principalIdentifier(): PrincipalIdentifier
    {
        return $this->principalIdentifier;
    }

    public function accountCategory(): AccountCategory
    {
        return $this->accountCategory;
    }
}
