<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Command\TranslateWiki;

use Source\Wiki\Wiki\Domain\Entity\DraftWiki;

class TranslateWikiOutput implements TranslateWikiOutputPort
{
    /** @var DraftWiki[]|null */
    private ?array $draftWikis = null;

    /**
     * @param DraftWiki[] $draftWikis
     */
    public function setDraftWikis(array $draftWikis): void
    {
        $this->draftWikis = $draftWikis;
    }

    /**
     * @return array{draftWikis: array<int, array{language: string, name: string, resourceType: string, status: string}>}
     */
    public function toArray(): array
    {
        if ($this->draftWikis === null) {
            return ['draftWikis' => []];
        }

        return [
            'draftWikis' => array_map(
                static fn (DraftWiki $draftWiki) => [
                    'language' => $draftWiki->language()->value,
                    'name' => (string) $draftWiki->basic()->name(),
                    'resourceType' => $draftWiki->resourceType()->value,
                    'status' => $draftWiki->status()->value,
                ],
                $this->draftWikis,
            ),
        ];
    }
}
