<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Infrastructure\Query;

use Application\Models\Wiki\WikiImage;
use DateTimeInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Source\Shared\Infrastructure\Support\ImageUrl;
use Source\Wiki\Image\Application\UseCase\Query\ListUploadedImages\ListUploadedImagesInputPort;
use Source\Wiki\Image\Application\UseCase\Query\ListUploadedImages\ListUploadedImagesInterface;
use Source\Wiki\Image\Application\UseCase\Query\ListUploadedImages\ListUploadedImagesOutputPort;
use Source\Wiki\Image\Application\UseCase\Query\UploadedImageReadModel;

readonly class ListUploadedImages implements ListUploadedImagesInterface
{
    public function process(ListUploadedImagesInputPort $input, ListUploadedImagesOutputPort $output): void
    {
        /** @var LengthAwarePaginator<int, WikiImage> $paginator */
        $paginator = WikiImage::query()
            ->where('wiki_id', $input->wikiIdentifier())
            ->orderBy('uploaded_at', 'desc')
            ->paginate($input->perPage());

        $output->output(
            array_map(
                fn (WikiImage $image): UploadedImageReadModel => $this->toReadModel($image),
                $paginator->items(),
            ),
            $paginator->currentPage(),
            $paginator->lastPage(),
            $paginator->total(),
            $paginator->perPage(),
        );
    }

    private function toReadModel(WikiImage $image): UploadedImageReadModel
    {
        return new UploadedImageReadModel(
            imageIdentifier: $image->id,
            url: ImageUrl::fromPath($image->image_path) ?? '',
            resourceType: $image->resource_type,
            wikiIdentifier: $image->wiki_id,
            imageUsage: $image->image_usage,
            displayOrder: $image->display_order,
            sourceUrl: $image->source_url,
            sourceName: $image->source_name,
            altText: $image->alt_text,
            isHidden: $image->is_hidden,
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
