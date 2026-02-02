<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Infrastructure\Factory;

use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Wiki\Domain\Entity\DraftWiki;
use Source\Wiki\Wiki\Domain\Factory\DraftWikiFactoryInterface;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\BasicInterface;
use Source\Wiki\Wiki\Domain\ValueObject\Section\SectionContentCollection;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;

readonly class DraftWikiFactory implements DraftWikiFactoryInterface
{
    public function __construct(
        private UuidGeneratorInterface $generator,
    ) {
    }

    public function create(
        ?PrincipalIdentifier $editorIdentifier,
        Language $language,
        ResourceType $resourceType,
        BasicInterface $basic,
        Slug $slug,
        ?TranslationSetIdentifier $translationSetIdentifier = null,
    ): DraftWiki {
        return new DraftWiki(
            new WikiIdentifier($this->generator->generate()),
            null,
            $translationSetIdentifier ?? new TranslationSetIdentifier($this->generator->generate()),
            $slug,
            $language,
            $resourceType,
            $basic,
            new SectionContentCollection([], allowBlocks: false),
            null,
            ApprovalStatus::Pending,
            $editorIdentifier,
        );
    }
}
