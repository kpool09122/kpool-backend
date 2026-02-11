<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Infrastructure\Factory;

use DateTimeImmutable;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Wiki\Wiki\Domain\Entity\Wiki;
use Source\Wiki\Wiki\Domain\Entity\WikiSnapshot;
use Source\Wiki\Wiki\Domain\Factory\WikiSnapshotFactoryInterface;
use Source\Wiki\Wiki\Domain\ValueObject\WikiSnapshotIdentifier;

readonly class WikiSnapshotFactory implements WikiSnapshotFactoryInterface
{
    public function __construct(
        private UuidGeneratorInterface $generator,
    ) {
    }

    public function create(Wiki $wiki): WikiSnapshot
    {
        return new WikiSnapshot(
            new WikiSnapshotIdentifier($this->generator->generate()),
            $wiki->wikiIdentifier(),
            $wiki->translationSetIdentifier(),
            $wiki->slug(),
            $wiki->language(),
            $wiki->resourceType(),
            $wiki->basic(),
            $wiki->sections(),
            $wiki->themeColor(),
            $wiki->version(),
            $wiki->editorIdentifier(),
            $wiki->approverIdentifier(),
            $wiki->mergerIdentifier(),
            $wiki->sourceEditorIdentifier(),
            $wiki->mergedAt(),
            $wiki->translatedAt(),
            $wiki->approvedAt(),
            new DateTimeImmutable('now'),
        );
    }
}
