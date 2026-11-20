<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query\ListDraftWikis;

use Source\Wiki\Wiki\Application\UseCase\Query\DraftWikiListItemReadModel;

interface ListDraftWikisOutputPort
{
    /**
     * @param list<DraftWikiListItemReadModel> $wikis
     */
    public function output(array $wikis, ?string $nextCursor, int $total, int $perPage): void;

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
