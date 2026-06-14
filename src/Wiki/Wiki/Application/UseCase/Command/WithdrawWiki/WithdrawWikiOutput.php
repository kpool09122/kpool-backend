<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Command\WithdrawWiki;

use Source\Wiki\Wiki\Domain\Entity\DraftWiki;

class WithdrawWikiOutput implements WithdrawWikiOutputPort
{
    private ?DraftWiki $draftWiki = null;

    public function setDraftWiki(DraftWiki $draftWiki): void
    {
        $this->draftWiki = $draftWiki;
    }

    /**
     * @return array{wikiIdentifier: ?string, language: ?string, name: ?string, resourceType: ?string, status: ?string}
     */
    public function toArray(): array
    {
        if ($this->draftWiki === null) {
            return [
                'wikiIdentifier' => null,
                'language' => null,
                'name' => null,
                'resourceType' => null,
                'status' => null,
            ];
        }

        return [
            'wikiIdentifier' => (string) $this->draftWiki->wikiIdentifier(),
            'language' => $this->draftWiki->language()->value,
            'name' => (string) $this->draftWiki->basic()->name(),
            'resourceType' => $this->draftWiki->resourceType()->value,
            'status' => $this->draftWiki->status()->value,
        ];
    }
}
