<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Infrastructure\Query;

use Application\Models\Wiki\DraftWikiImage;
use DateTimeInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Source\Wiki\Image\Application\UseCase\Query\DraftImageReadModel;
use Source\Wiki\Image\Application\UseCase\Query\ListDraftImages\ListDraftImagesInputPort;
use Source\Wiki\Image\Application\UseCase\Query\ListDraftImages\ListDraftImagesInterface;
use Source\Wiki\Image\Application\UseCase\Query\ListDraftImages\ListDraftImagesOutputPort;

readonly class ListDraftImages implements ListDraftImagesInterface
{
    public function process(ListDraftImagesInputPort $input, ListDraftImagesOutputPort $output): void
    {
        $query = DraftWikiImage::query()
            ->where('status', $input->status()->value)
            ->orderBy('uploaded_at', 'desc');

        if ($input->wikiIdentifier() !== null) {
            $query->where('wiki_id', (string) $input->wikiIdentifier());
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
            url: url($image->image_path),
            resourceType: $image->resource_type,
            wikiIdentifier: $image->wiki_id,
            imageUsage: $image->image_usage,
            displayOrder: $image->display_order,
            sourceUrl: $image->source_url,
            sourceName: $image->source_name,
            altText: $image->alt_text,
            status: $image->status,
            uploadedAt: $this->formatDateTime($image->uploaded_at),
        );
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
