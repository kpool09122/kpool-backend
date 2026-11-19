<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Command\SubmitWiki;

use Source\Wiki\Wiki\Domain\Entity\DraftWiki;

class SubmitWikiOutput implements SubmitWikiOutputPort
{
    /** @var list<DraftWiki> */
    private array $draftWikis = [];

    public function addDraftWiki(DraftWiki $draftWiki): void
    {
        $this->draftWikis[] = $draftWiki;
    }

    /**
     * @return list<array{wikiIdentifier: string, language: string, name: string, resourceType: string, status: string}>
     */
    public function toArray(): array
    {
        return array_map(static fn (DraftWiki $wiki): array => [
            'wikiIdentifier' => (string) $wiki->wikiIdentifier(),
            'language' => $wiki->language()->value,
            'name' => (string) $wiki->basic()->name(),
            'resourceType' => $wiki->resourceType()->value,
            'status' => $wiki->status()->value,
        ], $this->draftWikis);
    }
}
