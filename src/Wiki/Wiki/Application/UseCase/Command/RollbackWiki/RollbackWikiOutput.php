<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Command\RollbackWiki;

use Source\Wiki\Wiki\Domain\Entity\Wiki;

class RollbackWikiOutput implements RollbackWikiOutputPort
{
    /** @var Wiki[]|null */
    private ?array $wikis = null;

    /**
     * @param Wiki[] $wikis
     */
    public function setWikis(array $wikis): void
    {
        $this->wikis = $wikis;
    }

    /**
     * @return array{wikis: array<int, array{language: string, name: string, resourceType: string, version: int}>}
     */
    public function toArray(): array
    {
        if ($this->wikis === null) {
            return ['wikis' => []];
        }

        return [
            'wikis' => array_map(
                static fn (Wiki $wiki) => [
                    'language' => $wiki->language()->value,
                    'name' => (string) $wiki->basic()->name(),
                    'resourceType' => $wiki->resourceType()->value,
                    'version' => $wiki->version()->value(),
                ],
                $this->wikis,
            ),
        ];
    }
}
