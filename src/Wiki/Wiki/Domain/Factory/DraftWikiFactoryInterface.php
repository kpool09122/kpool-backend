<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Domain\Factory;

use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Wiki\Domain\Entity\DraftWiki;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\BasicInterface;

interface DraftWikiFactoryInterface
{
    public function create(
        ?PrincipalIdentifier $editorIdentifier,
        Language $language,
        ResourceType $resourceType,
        BasicInterface $basic,
        Slug $slug,
        ?TranslationSetIdentifier $translationSetIdentifier = null,
    ): DraftWiki;
}
