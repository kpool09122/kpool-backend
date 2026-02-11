<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Domain\Factory;

use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Wiki\Domain\Entity\Wiki;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\BasicInterface;

interface WikiFactoryInterface
{
    public function create(
        TranslationSetIdentifier $translationSetIdentifier,
        Slug $slug,
        Language $language,
        ResourceType $resourceType,
        BasicInterface $basic,
    ): Wiki;
}
