<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Infrastructure\Query;

use Application\Models\Wiki\Wiki as WikiModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Source\Shared\Infrastructure\Support\ImageUrl;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;
use Source\Wiki\Wiki\Application\UseCase\Query\ListRelatedProfiles\ListRelatedProfilesInputPort;
use Source\Wiki\Wiki\Application\UseCase\Query\ListRelatedProfiles\ListRelatedProfilesInterface;
use Source\Wiki\Wiki\Application\UseCase\Query\ListRelatedProfiles\ListRelatedProfilesOutputPort;
use Source\Wiki\Wiki\Application\UseCase\Query\RelatedProfileReadModel;

readonly class ListRelatedProfiles implements ListRelatedProfilesInterface
{
    /** @var array<string, array{relation: string, table: string}> */
    private const BASIC_TABLES = [
        ResourceType::AGENCY->value => ['relation' => 'agencyBasic', 'table' => 'wiki_agency_basics'],
        ResourceType::GROUP->value => ['relation' => 'groupBasic', 'table' => 'wiki_group_basics'],
        ResourceType::TALENT->value => ['relation' => 'talentBasic', 'table' => 'wiki_talent_basics'],
        ResourceType::SONG->value => ['relation' => 'songBasic', 'table' => 'wiki_song_basics'],
    ];

    /**
     * @throws WikiNotFoundException
     */
    public function process(ListRelatedProfilesInputPort $input, ListRelatedProfilesOutputPort $output): void
    {
        $source = WikiModel::query()
            ->with(['agencyBasic', 'groupBasic', 'talentBasic', 'songBasic'])
            ->where('language', $input->language()->value)
            ->where('slug', (string) $input->slug())
            ->whereIn('resource_type', array_keys(self::BASIC_TABLES))
            ->first();

        if ($source === null) {
            throw new WikiNotFoundException("Wiki not found for slug: {$input->slug()} and language: {$input->language()->value}");
        }

        if ($source->resource_type === $input->resourceType()->value) {
            throw new InvalidArgumentException('Source wiki and related profile resource type must be different.');
        }

        $targetBasic = self::BASIC_TABLES[$input->resourceType()->value] ?? null;
        if ($targetBasic === null) {
            throw new InvalidArgumentException('Unsupported related profile resource type.');
        }

        $query = WikiModel::query()
            ->select(
                'wikis.*',
                'wiki_images.image_path as image_path',
                'wiki_images.alt_text as image_alt_text',
                "{$targetBasic['table']}.name as profile_name",
                "{$targetBasic['table']}.normalized_name as profile_normalized_name",
            )
            ->join($targetBasic['table'], "{$targetBasic['table']}.wiki_id", '=', 'wikis.id')
            ->leftJoin('wiki_images', 'wiki_images.id', '=', 'wikis.image_identifier')
            ->where('wikis.language', $source->language)
            ->where('wikis.resource_type', $input->resourceType()->value);

        if (! $this->applyRelatedCondition($query, $source, $input->resourceType())) {
            $output->output([]);

            return;
        }

        $profiles = $query
            ->orderBy("{$targetBasic['table']}.normalized_name")
            ->orderBy('wikis.id')
            ->get()
            ->map(fn (WikiModel $wiki): RelatedProfileReadModel => $this->toReadModel($wiki))
            ->values()
            ->all();

        $output->output($profiles);
    }

    /**
     * @param Builder<WikiModel> $query
     */
    private function applyRelatedCondition(Builder $query, WikiModel $source, ResourceType $targetType): bool
    {
        return match ($source->resource_type) {
            ResourceType::AGENCY->value => $this->applyFromAgency($query, $source, $targetType),
            ResourceType::GROUP->value => $this->applyFromGroup($query, $source, $targetType),
            ResourceType::TALENT->value => $this->applyFromTalent($query, $source, $targetType),
            ResourceType::SONG->value => $this->applyFromSong($query, $source, $targetType),
            default => false,
        };
    }

    /**
     * @param Builder<WikiModel> $query
     */
    private function applyFromAgency(Builder $query, WikiModel $source, ResourceType $targetType): bool
    {
        $agencyIdentifierColumn = match ($targetType) {
            ResourceType::GROUP => 'wiki_group_basics.agency_identifier',
            ResourceType::TALENT => 'wiki_talent_basics.agency_identifier',
            ResourceType::SONG => 'wiki_song_basics.agency_identifier',
            default => null,
        };

        if ($agencyIdentifierColumn === null) {
            return false;
        }

        $query->where($agencyIdentifierColumn, $source->id);

        return true;
    }

    /**
     * @param Builder<WikiModel> $query
     */
    private function applyFromGroup(Builder $query, WikiModel $source, ResourceType $targetType): bool
    {
        return match ($targetType) {
            ResourceType::AGENCY => $this->applyAgencyIdentifierCondition($query, $this->agencyIdentifier($source)),
            ResourceType::TALENT => $this->applyPivotCondition($query, 'wiki_talent_basic_groups', 'wiki_id', 'group_identifier', $source->id),
            ResourceType::SONG => $this->applyPivotCondition($query, 'wiki_song_basic_groups', 'wiki_id', 'group_identifier', $source->id),
            default => false,
        };
    }

    /**
     * @param Builder<WikiModel> $query
     */
    private function applyFromTalent(Builder $query, WikiModel $source, ResourceType $targetType): bool
    {
        return match ($targetType) {
            ResourceType::AGENCY => $this->applyAgencyIdentifierCondition($query, $this->agencyIdentifier($source)),
            ResourceType::GROUP => $this->applyPivotCondition($query, 'wiki_talent_basic_groups', 'group_identifier', 'wiki_id', $source->id),
            ResourceType::SONG => $this->applyPivotCondition($query, 'wiki_song_basic_talents', 'wiki_id', 'talent_identifier', $source->id),
            default => false,
        };
    }

    /**
     * @param Builder<WikiModel> $query
     */
    private function applyFromSong(Builder $query, WikiModel $source, ResourceType $targetType): bool
    {
        return match ($targetType) {
            ResourceType::AGENCY => $this->applyAgencyIdentifierCondition($query, $this->agencyIdentifier($source)),
            ResourceType::GROUP => $this->applyPivotCondition($query, 'wiki_song_basic_groups', 'group_identifier', 'wiki_id', $source->id),
            ResourceType::TALENT => $this->applyPivotCondition($query, 'wiki_song_basic_talents', 'talent_identifier', 'wiki_id', $source->id),
            default => false,
        };
    }

    /**
     * @param Builder<WikiModel> $query
     */
    private function applyAgencyIdentifierCondition(Builder $query, ?string $agencyIdentifier): bool
    {
        if ($agencyIdentifier === null) {
            return false;
        }

        $query->where('wikis.id', $agencyIdentifier);

        return true;
    }

    /**
     * @param Builder<WikiModel> $query
     */
    private function applyPivotCondition(
        Builder $query,
        string $table,
        string $targetColumn,
        string $sourceColumn,
        string $sourceIdentifier,
    ): bool {
        $query->whereExists(function (\Illuminate\Database\Query\Builder $subQuery) use ($table, $targetColumn, $sourceColumn, $sourceIdentifier): void {
            $subQuery->selectRaw('1')
                ->from($table)
                ->whereColumn("{$table}.{$targetColumn}", 'wikis.id')
                ->where("{$table}.{$sourceColumn}", $sourceIdentifier);
        });

        return true;
    }

    private function agencyIdentifier(WikiModel $wiki): ?string
    {
        $basic = $this->sourceBasicModel($wiki);

        $agencyIdentifier = $basic->getAttribute('agency_identifier');

        return $agencyIdentifier === null ? null : (string) $agencyIdentifier;
    }

    private function sourceBasicModel(WikiModel $wiki): Model
    {
        $relation = self::BASIC_TABLES[$wiki->resource_type]['relation'] ?? null;
        if ($relation === null) {
            throw new InvalidArgumentException("Unsupported wiki resource type: {$wiki->resource_type}");
        }

        $basic = $wiki->{$relation};
        if (! $basic instanceof Model) {
            throw new InvalidArgumentException("Basic not found for Wiki: {$wiki->id}");
        }

        return $basic;
    }

    private function toReadModel(WikiModel $wiki): RelatedProfileReadModel
    {
        return new RelatedProfileReadModel(
            wikiIdentifier: $wiki->id,
            slug: $wiki->slug,
            language: $wiki->language,
            resourceType: $wiki->resource_type,
            name: (string) $wiki->getAttribute('profile_name'),
            normalizedName: (string) $wiki->getAttribute('profile_normalized_name'),
            imageIdentifier: $wiki->image_identifier,
            imageUrl: ImageUrl::fromPath($wiki->getAttribute('image_path')),
            imageAltText: $wiki->getAttribute('image_alt_text'),
        );
    }
}
