<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Infrastructure\Query;

use Application\Models\Wiki\DraftWiki;
use Application\Models\Wiki\DraftWikiAgencyBasic;
use Application\Models\Wiki\DraftWikiGroupBasic;
use Application\Models\Wiki\DraftWikiSongBasic;
use Application\Models\Wiki\DraftWikiTalentBasic;
use DateTimeInterface;
use Illuminate\Contracts\Pagination\CursorPaginator;
use InvalidArgumentException;
use Source\Shared\Domain\Support\TypedValue;
use Source\Shared\Infrastructure\Support\ImageUrl;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Application\UseCase\Query\DraftWikiListItemReadModel;
use Source\Wiki\Wiki\Application\UseCase\Query\ListMyDraftWikis\ListMyDraftWikisInputPort;
use Source\Wiki\Wiki\Application\UseCase\Query\ListMyDraftWikis\ListMyDraftWikisInterface;
use Source\Wiki\Wiki\Application\UseCase\Query\ListMyDraftWikis\ListMyDraftWikisOutputPort;

readonly class ListMyDraftWikis implements ListMyDraftWikisInterface
{
    /** @var array<string, string> */
    private const BASIC_RELATIONS = [
        ResourceType::TALENT->value => 'talentBasic',
        ResourceType::GROUP->value => 'groupBasic',
        ResourceType::AGENCY->value => 'agencyBasic',
        ResourceType::SONG->value => 'songBasic',
    ];

    public function process(ListMyDraftWikisInputPort $input, ListMyDraftWikisOutputPort $output): void
    {
        $query = DraftWiki::query()
            ->with([
                'image',
                'talentBasic.groups',
                'groupBasic',
                'agencyBasic',
                'songBasic.groups',
                'songBasic.talents',
            ])
            ->whereIn(
                'draft_wikis.status',
                array_map(static fn (ApprovalStatus $status): string => $status->value, $input->statuses()),
            )
            ->where('draft_wikis.editor_id', (string) $input->editorIdentifier())
            ->select('draft_wikis.*')
            // PostgreSQL DESC puts null dates first. Cursor comparisons need non-null values.
            ->selectRaw("COALESCE(draft_wikis.edited_at, 'infinity'::timestamp) as cursor_edited_at")
            ->selectRaw("COALESCE(draft_wikis.updated_at, 'infinity'::timestamp) as cursor_updated_at")
            ->orderBy('cursor_edited_at', 'desc')
            ->orderBy('cursor_updated_at', 'desc')
            ->orderBy('id');

        if ($input->translationSetIdentifier() !== null) {
            $query->where('draft_wikis.translation_set_identifier', (string) $input->translationSetIdentifier());
        }

        if ($input->resourceType() !== null) {
            $query->where('draft_wikis.resource_type', $input->resourceType()->value);
        } else {
            $query->whereIn('draft_wikis.resource_type', array_keys(self::BASIC_RELATIONS));
        }

        $total = (clone $query)->count();

        /** @var CursorPaginator<int, DraftWiki> $paginator */
        $paginator = $query->cursorPaginate(
            perPage: $input->perPage(),
            cursor: $input->cursor(),
        );

        $output->output(
            array_map(
                fn (DraftWiki $wiki): DraftWikiListItemReadModel => $this->toReadModel($wiki),
                array_values($paginator->items()),
            ),
            $paginator->nextCursor()?->encode(),
            $total,
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
            fontStyle: $wiki->font_style,
            title: $wiki->title,
            metaDescription: $wiki->meta_description,
            keywords: $wiki->keywords,
            imageIdentifier: $wiki->image_identifier,
            imageUrl: ImageUrl::fromPath($wiki->image?->image_path),
            imageAltText: $wiki->image?->alt_text,
            isHidden: $wiki->image?->is_hidden,
            status: $wiki->status,
            rejectionReason: $wiki->rejection_reason,
            name: $basic->name,
            normalizedName: $basic->normalized_name,
            editedAt: $this->formatDateTime($wiki->edited_at),
            approvedAt: $this->formatDateTime($wiki->approved_at),
            translatedAt: $this->formatDateTime($wiki->translated_at),
            mergedAt: $this->formatDateTime($wiki->merged_at),
        );
    }

    private function basicModel(DraftWiki $wiki): DraftWikiTalentBasic|DraftWikiGroupBasic|DraftWikiAgencyBasic|DraftWikiSongBasic
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

        return TypedValue::string($dateTime);
    }
}
