<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Domain\Repository;

use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Wiki\Domain\Entity\Wiki;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;

interface WikiRepositoryInterface
{
    public function findById(WikiIdentifier $wikiIdentifier): ?Wiki;

    public function findBySlugAndLanguage(Slug $slug, Language $language): ?Wiki;

    public function existsBySlug(Slug $slug): bool;

    /**
     * @return Wiki[]
     */
    public function findByTranslationSetIdentifier(TranslationSetIdentifier $translationSetIdentifier): array;

    /**
     * @return Wiki[]
     */
    public function findByResourceType(ResourceType $resourceType, int $limit = 20, int $offset = 0): array;

    public function save(Wiki $wiki): void;

    public function delete(Wiki $wiki): void;
}
