<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Infrastructure\Query;

use Application\Models\Wiki\Wiki as WikiModel;
use Application\Models\Wiki\WikiAgencyBasic;
use Application\Models\Wiki\WikiGroupBasic;
use Application\Models\Wiki\WikiSongBasic;
use Application\Models\Wiki\WikiTalentBasic;
use DateTimeInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Source\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Infrastructure\Support\ImageUrl;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Application\UseCase\Query\ListMyOwnedWikis\ListMyOwnedWikisInputPort;
use Source\Wiki\Wiki\Application\UseCase\Query\ListMyOwnedWikis\ListMyOwnedWikisInterface;
use Source\Wiki\Wiki\Application\UseCase\Query\ListMyOwnedWikis\ListMyOwnedWikisOutputPort;
use Source\Wiki\Wiki\Application\UseCase\Query\WikiListItemReadModel;

readonly class ListMyOwnedWikis implements ListMyOwnedWikisInterface
{
    /** @var array<string, string> */
    private const BASIC_RELATIONS = [
        ResourceType::TALENT->value => 'talentBasic',
        ResourceType::GROUP->value => 'groupBasic',
        ResourceType::AGENCY->value => 'agencyBasic',
        ResourceType::SONG->value => 'songBasic',
    ];

    public function process(ListMyOwnedWikisInputPort $input, ListMyOwnedWikisOutputPort $output): void
    {
        $primaryResourceType = $this->primaryResourceType($input->accountCategory());
        $primaryOwnedWikis = $primaryResourceType === null
            ? []
            : array_map(
                fn (WikiModel $wiki): WikiListItemReadModel => $this->toReadModel($wiki),
                $this->baseQuery($input)
                    ->where('wikis.resource_type', $primaryResourceType)
                    ->get()
                    ->all(),
            );

        $otherOwnedWikisQuery = $this->baseQuery($input);
        if ($primaryResourceType !== null) {
            $otherOwnedWikisQuery->where('wikis.resource_type', '<>', $primaryResourceType);
        }

        /** @var LengthAwarePaginator<int, WikiModel> $otherOwnedWikisPaginator */
        $otherOwnedWikisPaginator = $otherOwnedWikisQuery->paginate($input->perPage());

        $output->output(
            $input->accountCategory(),
            $primaryOwnedWikis,
            array_map(
                fn (WikiModel $wiki): WikiListItemReadModel => $this->toReadModel($wiki),
                $otherOwnedWikisPaginator->items(),
            ),
            $otherOwnedWikisPaginator->currentPage(),
            $otherOwnedWikisPaginator->lastPage(),
            $otherOwnedWikisPaginator->total(),
            $otherOwnedWikisPaginator->perPage(),
        );
    }

    private function primaryResourceType(AccountCategory $accountCategory): ?string
    {
        return match ($accountCategory) {
            AccountCategory::AGENCY => ResourceType::AGENCY->value,
            AccountCategory::TALENT => ResourceType::TALENT->value,
            AccountCategory::GENERAL => null,
        };
    }

    /** @return Builder<WikiModel> */
    private function baseQuery(ListMyOwnedWikisInputPort $input): Builder
    {
        return WikiModel::query()
            ->select('wikis.*', 'wiki_images.image_path as image_path', 'wiki_images.alt_text as image_alt_text', 'wiki_images.is_hidden as image_is_hidden')
            ->leftJoin('wiki_images', 'wiki_images.id', '=', 'wikis.image_identifier')
            ->with(['talentBasic', 'groupBasic', 'agencyBasic', 'songBasic'])
            ->where('wikis.owner_account_id', (string) $input->accountIdentifier())
            ->whereIn('wikis.resource_type', array_keys(self::BASIC_RELATIONS))
            ->orderBy('wikis.updated_at', 'desc')
            ->orderBy('wikis.id');
    }

    private function toReadModel(WikiModel $wiki): WikiListItemReadModel
    {
        $basic = $this->basicModel($wiki);

        return new WikiListItemReadModel(
            wikiIdentifier: $wiki->id,
            translationSetIdentifier: $wiki->translation_set_identifier,
            slug: $wiki->slug,
            language: $wiki->language,
            resourceType: $wiki->resource_type,
            version: $wiki->version,
            themeColor: $wiki->theme_color,
            fontStyle: $wiki->font_style,
            title: $wiki->title,
            metaDescription: $wiki->meta_description,
            keywords: $wiki->keywords,
            imageIdentifier: $wiki->image_identifier,
            imageUrl: ImageUrl::fromPath($wiki->getAttribute('image_path')),
            imageAltText: $wiki->getAttribute('image_alt_text'),
            isHidden: $this->nullableBool($wiki->getAttribute('image_is_hidden')),
            name: (string) $basic->getAttribute('name'),
            normalizedName: (string) $basic->getAttribute('normalized_name'),
            publishedAt: $this->formatDateTime($wiki->published_at),
            updatedAt: $this->formatDateTime($wiki->updated_at),
            isOfficial: $wiki->owner_account_id !== null,
        );
    }

    private function basicModel(WikiModel $wiki): Model
    {
        $relation = self::BASIC_RELATIONS[$wiki->resource_type] ?? null;
        if ($relation === null) {
            throw new InvalidArgumentException("Unsupported wiki resource type: {$wiki->resource_type}");
        }

        $basic = $wiki->{$relation};
        if (
            ! $basic instanceof WikiTalentBasic
            && ! $basic instanceof WikiGroupBasic
            && ! $basic instanceof WikiAgencyBasic
            && ! $basic instanceof WikiSongBasic
        ) {
            throw new InvalidArgumentException("Basic not found for Wiki: {$wiki->id}");
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

    private function nullableBool(mixed $value): ?bool
    {
        return $value === null ? null : (bool) $value;
    }
}
