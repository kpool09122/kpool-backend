<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Domain\Repository;

use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Wiki\Domain\Entity\DraftWiki;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;

interface DraftWikiRepositoryInterface
{
    public function findById(WikiIdentifier $wikiIdentifier): ?DraftWiki;

    public function findBySlugAndLanguage(Slug $slug, Language $language): ?DraftWiki;

    public function findByPublishedWikiIdentifier(WikiIdentifier $wikiIdentifier): ?DraftWiki;

    /**
     * @return DraftWiki[]
     */
    public function findByTranslationSetIdentifier(TranslationSetIdentifier $translationSetIdentifier): array;

    /**
     * @return DraftWiki[]
     */
    public function findByEditorIdentifier(PrincipalIdentifier $editorIdentifier, int $limit = 20, int $offset = 0): array;

    /**
     * @return DraftWiki[]
     */
    public function findByStatus(ApprovalStatus $status, int $limit = 20, int $offset = 0): array;

    /**
     * @return DraftWiki[]
     */
    public function findByResourceType(ResourceType $resourceType, int $limit = 20, int $offset = 0): array;

    public function save(DraftWiki $draftWiki): void;

    public function delete(DraftWiki $draftWiki): void;
}
