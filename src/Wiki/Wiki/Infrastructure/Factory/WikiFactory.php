<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Infrastructure\Factory;

use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Wiki\Domain\Entity\Wiki;
use Source\Wiki\Wiki\Domain\Factory\WikiFactoryInterface;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\BasicInterface;
use Source\Wiki\Wiki\Domain\ValueObject\Section\SectionContentCollection;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;

readonly class WikiFactory implements WikiFactoryInterface
{
    public function __construct(
        private UuidGeneratorInterface $generator,
    ) {
    }

    public function create(
        TranslationSetIdentifier $translationSetIdentifier,
        Slug $slug,
        Language $language,
        ResourceType $resourceType,
        BasicInterface $basic,
    ): Wiki {
        return new Wiki(
            new WikiIdentifier($this->generator->generate()),
            $translationSetIdentifier,
            $slug,
            $language,
            $resourceType,
            $basic,
            new SectionContentCollection([], allowBlocks: false),
            null,
            new Version(1),
        );
    }
}
