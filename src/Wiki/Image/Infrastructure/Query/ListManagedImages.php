<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Infrastructure\Query;

use Application\Models\Wiki\DraftWikiImage;
use Application\Models\Wiki\Wiki;
use Application\Models\Wiki\WikiImage;
use DateTimeInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Source\Shared\Domain\Support\TypedValue;
use Source\Shared\Infrastructure\Support\ImageUrl;
use Source\Wiki\Image\Application\UseCase\Query\ListManagedImages\ListManagedImagesInputPort;
use Source\Wiki\Image\Application\UseCase\Query\ListManagedImages\ListManagedImagesInterface;
use Source\Wiki\Image\Application\UseCase\Query\ListManagedImages\ListManagedImagesOutputPort;
use Source\Wiki\Image\Application\UseCase\Query\ManagedImageReadModel;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\Resource;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

readonly class ListManagedImages implements ListManagedImagesInterface
{
    public function __construct(private PrincipalRepositoryInterface $principalRepository, private PolicyEvaluatorInterface $policyEvaluator)
    {
    }

    public function process(ListManagedImagesInputPort $input, ListManagedImagesOutputPort $output): void
    {
        $principal = $this->principalRepository->findById($input->principalIdentifier());
        if ($principal === null) {
            throw new PrincipalNotFoundException();
        }
        if (! $this->policyEvaluator->evaluate($principal, Action::LIST_MANAGED_IMAGES, new Resource(ResourceType::IMAGE))) {
            throw new DisallowedException();
        }
        $query = ($input->status() === ApprovalStatus::Approved ? WikiImage::query() : DraftWikiImage::query())
            ->with([
                'wikis.talentBasic',
                'wikis.groupBasic',
                'wikis.agencyBasic',
                'wikis.songBasic',
            ])
            ->orderBy('uploaded_at', 'desc')->orderBy('id');

        if ($input->status() !== ApprovalStatus::Approved) {
            $query->where('status', $input->status()->value);
        }

        /** @var LengthAwarePaginator<int, DraftWikiImage|WikiImage> $paginator */
        $paginator = $query->paginate($input->perPage());

        $output->output(
            array_map(
                fn (DraftWikiImage|WikiImage $image): ManagedImageReadModel => $this->toReadModel($image),
                array_values($paginator->items()),
            ),
            $paginator->currentPage(),
            $paginator->lastPage(),
            $paginator->total(),
            $paginator->perPage(),
        );
    }

    private function toReadModel(DraftWikiImage|WikiImage $image): ManagedImageReadModel
    {
        return new ManagedImageReadModel(
            imageIdentifier: $image->id,
            url: ImageUrl::fromPath($image->image_path) ?? '',
            resourceType: $image->resource_type,
            translationSetIdentifier: $image->translation_set_identifier,
            displayOrder: $image->display_order,
            sourceUrl: $image->source_url,
            sourceName: $image->source_name,
            author: $image->author,
            license: $image->license,
            altText: $image->alt_text,
            wiki: $this->wikiDisplayInformation($image),
            status: $image instanceof WikiImage ? ApprovalStatus::Approved->value : $image->status,
            isHidden: $image instanceof WikiImage && $image->is_hidden,
            uploadedAt: $this->formatDateTime($image->uploaded_at),
        );
    }

    /**
     * @return array{names: array<string, string>, slug: string}
     */
    private function wikiDisplayInformation(DraftWikiImage|WikiImage $image): array
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

        return TypedValue::string($basic->name);
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
