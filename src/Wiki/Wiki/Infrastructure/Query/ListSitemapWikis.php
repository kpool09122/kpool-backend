<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Infrastructure\Query;

use Application\Models\Wiki\Wiki as WikiModel;
use DateTimeInterface;
use Illuminate\Pagination\Cursor;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Application\UseCase\Query\ListSitemapWikis\ListSitemapWikisInputPort;
use Source\Wiki\Wiki\Application\UseCase\Query\ListSitemapWikis\ListSitemapWikisInterface;
use Source\Wiki\Wiki\Application\UseCase\Query\ListSitemapWikis\ListSitemapWikisOutputPort;
use Source\Wiki\Wiki\Application\UseCase\Query\SitemapWikiReadModel;

readonly class ListSitemapWikis implements ListSitemapWikisInterface
{
    public function process(ListSitemapWikisInputPort $input, ListSitemapWikisOutputPort $output): void
    {
        $paginator = WikiModel::query()
            ->select(['id', 'slug', 'language', 'resource_type', 'published_at'])
            ->whereIn('resource_type', [ResourceType::AGENCY->value, ResourceType::GROUP->value, ResourceType::TALENT->value, ResourceType::SONG->value])
            ->orderBy('id')
            ->cursorPaginate(
                perPage: $input->perPage(),
                cursor: $input->after() === null ? null : new Cursor(['id' => (string) $input->after()]),
            );

        $output->output(
            array_values($paginator->getCollection()->map(static fn (WikiModel $wiki): SitemapWikiReadModel => new SitemapWikiReadModel(
                slug: $wiki->slug,
                language: $wiki->language,
                resourceType: $wiki->resource_type,
                lastModifiedAt: $wiki->published_at?->format(DateTimeInterface::ATOM),
            ))->all()),
            $paginator->nextCursor()?->parameter('id'),
        );
    }
}
