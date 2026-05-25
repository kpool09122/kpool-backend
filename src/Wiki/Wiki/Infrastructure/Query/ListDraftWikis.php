<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Infrastructure\Query;

use Application\Models\Wiki\DraftWiki;
use Application\Models\Wiki\DraftWikiAgencyBasic;
use Application\Models\Wiki\DraftWikiGroupBasic;
use Application\Models\Wiki\DraftWikiSongBasic;
use Application\Models\Wiki\DraftWikiTalentBasic;
use DateTimeInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Source\Shared\Infrastructure\Support\ImageUrl;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Application\UseCase\Query\DraftWikiListItemReadModel;
use Source\Wiki\Wiki\Application\UseCase\Query\ListDraftWikis\ListDraftWikisInputPort;
use Source\Wiki\Wiki\Application\UseCase\Query\ListDraftWikis\ListDraftWikisInterface;
use Source\Wiki\Wiki\Application\UseCase\Query\ListDraftWikis\ListDraftWikisOutputPort;

readonly class ListDraftWikis implements ListDraftWikisInterface
{
    /** @var array<string, string> */
    private const BASIC_RELATIONS = [
        ResourceType::TALENT->value => 'talentBasic',
        ResourceType::GROUP->value => 'groupBasic',
        ResourceType::AGENCY->value => 'agencyBasic',
        ResourceType::SONG->value => 'songBasic',
    ];

    public function process(ListDraftWikisInputPort $input, ListDraftWikisOutputPort $output): void
    {
        $query = DraftWiki::query()
            ->select('draft_wikis.*', 'wiki_images.image_path as image_path', 'wiki_images.alt_text as image_alt_text')
            ->leftJoin('wiki_images', 'wiki_images.id', '=', 'draft_wikis.image_identifier')
            ->with(['talentBasic', 'groupBasic', 'agencyBasic', 'songBasic'])
            ->where('draft_wikis.status', $input->status()->value)
            ->orderBy('draft_wikis.edited_at', 'desc')
            ->orderBy('draft_wikis.updated_at', 'desc');

        if ($input->translationSetIdentifier() !== null) {
            $query->where('draft_wikis.translation_set_identifier', (string) $input->translationSetIdentifier());
        }

        if ($input->editorIdentifier() !== null) {
            $query->where('draft_wikis.editor_id', (string) $input->editorIdentifier());
        }

        if ($input->resourceType() !== null) {
            $query->where('draft_wikis.resource_type', $input->resourceType()->value);
        } else {
            $query->whereIn('draft_wikis.resource_type', array_keys(self::BASIC_RELATIONS));
        }

        /** @var LengthAwarePaginator<int, DraftWiki> $paginator */
        $paginator = $query->paginate($input->perPage());

        $output->output(
            array_map(
                fn (DraftWiki $wiki): DraftWikiListItemReadModel => $this->toReadModel($wiki),
                $paginator->items(),
            ),
            $paginator->currentPage(),
            $paginator->lastPage(),
            $paginator->total(),
            $paginator->perPage(),
        );
    }

    private function toReadModel(DraftWiki $wiki): DraftWikiListItemReadModel
    {
        $basic = $this->basicModel($wiki);

        return new DraftWikiListItemReadModel(
            wikiIdentifier: $wiki->id,
            publishedWikiIdentifier: $wiki->published_wiki_id,
            translationSetIdentifier: $wiki->translation_set_identifier,
            slug: $wiki->slug,
            language: $wiki->language,
            resourceType: $wiki->resource_type,
            themeColor: $wiki->theme_color,
            imageIdentifier: $wiki->image_identifier,
            imageUrl: ImageUrl::fromPath($wiki->getAttribute('image_path')),
            imageAltText: $wiki->getAttribute('image_alt_text'),
            status: $wiki->status,
            name: (string) $basic->getAttribute('name'),
            normalizedName: (string) $basic->getAttribute('normalized_name'),
            editedAt: $this->formatDateTime($wiki->edited_at),
            approvedAt: $this->formatDateTime($wiki->approved_at),
            translatedAt: $this->formatDateTime($wiki->translated_at),
            mergedAt: $this->formatDateTime($wiki->merged_at),
        );
    }

    private function basicModel(DraftWiki $wiki): Model
    {
        $relation = self::BASIC_RELATIONS[$wiki->resource_type] ?? null;
        if ($relation === null) {
            throw new InvalidArgumentException("Unsupported draft wiki resource type: {$wiki->resource_type}");
        }

        $basic = $wiki->{$relation};
        if (
            ! $basic instanceof DraftWikiTalentBasic
            && ! $basic instanceof DraftWikiGroupBasic
            && ! $basic instanceof DraftWikiAgencyBasic
            && ! $basic instanceof DraftWikiSongBasic
        ) {
            throw new InvalidArgumentException("Basic not found for DraftWiki: {$wiki->id}");
        }

        return $basic;
    }

    private function formatDateTime(mixed $dateTime): ?string
    {
        if ($dateTime === null) {
            return null;
        }

        if ($dateTime instanceof DateTimeInterface) {
            return $dateTime->format(DateTimeInterface::ATOM);
        }

        return (string) $dateTime;
    }
}
