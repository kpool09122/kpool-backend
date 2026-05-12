<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Infrastructure\Query;

use Application\Models\Wiki\DraftWikiImage;
use Application\Models\Wiki\Wiki;
use DateTimeInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Source\Shared\Infrastructure\Support\ImageUrl;
use Source\Wiki\Image\Application\UseCase\Query\DraftImageReadModel;
use Source\Wiki\Image\Application\UseCase\Query\ListDraftImages\ListDraftImagesInputPort;
use Source\Wiki\Image\Application\UseCase\Query\ListDraftImages\ListDraftImagesInterface;
use Source\Wiki\Image\Application\UseCase\Query\ListDraftImages\ListDraftImagesOutputPort;

readonly class ListDraftImages implements ListDraftImagesInterface
{
    public function process(ListDraftImagesInputPort $input, ListDraftImagesOutputPort $output): void
    {
        $query = DraftWikiImage::query()
            ->with([
                'wikis.talentBasic',
                'wikis.groupBasic',
                'wikis.agencyBasic',
                'wikis.songBasic',
            ])
            ->where('status', $input->status()->value)
            ->orderBy('uploaded_at', 'desc');

        if ($input->translationSetIdentifier() !== null) {
            $query->where('translation_set_identifier', (string) $input->translationSetIdentifier());
        }

        /** @var LengthAwarePaginator<int, DraftWikiImage> $paginator */
        $paginator = $query->paginate($input->perPage());

        $output->output(
            array_map(
                fn (DraftWikiImage $image): DraftImageReadModel => $this->toReadModel($image),
                $paginator->items(),
            ),
            $paginator->currentPage(),
            $paginator->lastPage(),
            $paginator->total(),
            $paginator->perPage(),
        );
    }

    private function toReadModel(DraftWikiImage $image): DraftImageReadModel
    {
        return new DraftImageReadModel(
            imageIdentifier: $image->id,
            publishedImageIdentifier: $image->published_id,
            url: ImageUrl::fromPath($image->image_path) ?? '',
            resourceType: $image->resource_type,
            translationSetIdentifier: $image->translation_set_identifier,
            imageUsage: $image->image_usage,
            displayOrder: $image->display_order,
            sourceUrl: $image->source_url,
            sourceName: $image->source_name,
            altText: $image->alt_text,
            wiki: $this->wikiDisplayInformation($image),
            status: $image->status,
            uploadedAt: $this->formatDateTime($image->uploaded_at),
        );
    }

    /**
     * @return array{names: array<string, string>, slug: string}
     */
    private function wikiDisplayInformation(DraftWikiImage $image): array
    {
        $names = [];
        $slug = '';

        foreach ($image->wikis as $wiki) {
            $names[$wiki->language] = $this->wikiName($wiki);
            // Slug is unique per language, so the first linked wiki is enough as the representative slug.
            if ($slug === '') {
                $slug = $wiki->slug;
            }
        }

        return [
            'names' => $names,
            'slug' => $slug,
        ];
    }

    private function wikiName(Wiki $wiki): string
    {
        $basic = match ($wiki->resource_type) {
            'talent' => $wiki->getRelationValue('talentBasic'),
            'group' => $wiki->getRelationValue('groupBasic'),
            'agency' => $wiki->getRelationValue('agencyBasic'),
            'song' => $wiki->getRelationValue('songBasic'),
            default => null,
        };

        if (! is_object($basic) || ! isset($basic->name)) {
            return '';
        }

        return (string) $basic->name;
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
