<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Infrastructure\Repository;

use Application\Models\Wiki\WikiSnapshot as WikiSnapshotModel;
use Application\Models\Wiki\WikiSnapshotAgencyBasic;
use Application\Models\Wiki\WikiSnapshotGroupBasic;
use Application\Models\Wiki\WikiSnapshotSongBasic;
use Application\Models\Wiki\WikiSnapshotTalentBasic;
use InvalidArgumentException;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Wiki\Domain\Entity\WikiSnapshot;
use Source\Wiki\Wiki\Domain\Repository\WikiSnapshotRepositoryInterface;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Agency\AgencyBasic;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Group\GroupBasic;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\BasicInterface;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Song\SongBasic;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Talent\TalentBasic;
use Source\Wiki\Wiki\Domain\ValueObject\Color;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Source\Wiki\Wiki\Domain\ValueObject\WikiSnapshotIdentifier;

readonly class WikiSnapshotRepository implements WikiSnapshotRepositoryInterface
{
    public function save(WikiSnapshot $snapshot): void
    {
        $snapshotId = (string) $snapshot->snapshotIdentifier();

        WikiSnapshotModel::query()->updateOrCreate(
            [
                'id' => $snapshotId,
            ],
            [
                'wiki_id' => (string) $snapshot->wikiIdentifier(),
                'translation_set_identifier' => (string) $snapshot->translationSetIdentifier(),
                'slug' => (string) $snapshot->slug(),
                'language' => $snapshot->language()->value,
                'resource_type' => $snapshot->resourceType()->value,
                'sections' => SectionContentMapper::collectionToArray($snapshot->sections()),
                'theme_color' => $snapshot->themeColor() ? (string) $snapshot->themeColor() : null,
                'version' => $snapshot->version()->value(),
                'editor_id' => $snapshot->editorIdentifier() ? (string) $snapshot->editorIdentifier() : null,
                'approver_id' => $snapshot->approverIdentifier() ? (string) $snapshot->approverIdentifier() : null,
                'merger_id' => $snapshot->mergerIdentifier() ? (string) $snapshot->mergerIdentifier() : null,
                'source_editor_id' => $snapshot->sourceEditorIdentifier() ? (string) $snapshot->sourceEditorIdentifier() : null,
                'merged_at' => $snapshot->mergedAt(),
                'translated_at' => $snapshot->translatedAt(),
                'approved_at' => $snapshot->approvedAt(),
            ]
        );

        $this->saveBasic($snapshotId, $snapshot->resourceType(), $snapshot->basic());
    }

    /**
     * @return WikiSnapshot[]
     */
    public function findByWikiIdentifier(WikiIdentifier $wikiIdentifier): array
    {
        $models = WikiSnapshotModel::query()
            ->with(['talentBasic', 'groupBasic', 'agencyBasic', 'songBasic'])
            ->where('wiki_id', (string) $wikiIdentifier)
            ->orderBy('version', 'desc')
            ->get();

        return $models->map(fn (WikiSnapshotModel $model) => $this->toDomainEntity($model))->toArray();
    }

    public function findByWikiAndVersion(
        WikiIdentifier $wikiIdentifier,
        Version $version,
    ): ?WikiSnapshot {
        $model = WikiSnapshotModel::query()
            ->with(['talentBasic', 'groupBasic', 'agencyBasic', 'songBasic'])
            ->where('wiki_id', (string) $wikiIdentifier)
            ->where('version', $version->value())
            ->first();

        if ($model === null) {
            return null;
        }

        return $this->toDomainEntity($model);
    }

    /**
     * @inheritDoc
     */
    public function findByTranslationSetIdentifierAndVersion(
        TranslationSetIdentifier $translationSetIdentifier,
        Version $version,
    ): array {
        $models = WikiSnapshotModel::query()
            ->with(['talentBasic', 'groupBasic', 'agencyBasic', 'songBasic'])
            ->where('translation_set_identifier', (string) $translationSetIdentifier)
            ->where('version', $version->value())
            ->get();

        return $models->map(fn (WikiSnapshotModel $model) => $this->toDomainEntity($model))->toArray();
    }

    private function saveBasic(string $snapshotId, ResourceType $resourceType, BasicInterface $basic): void
    {
        $basicArray = $basic->toArray();
        unset($basicArray['type']);

        match ($resourceType) {
            ResourceType::TALENT => WikiSnapshotTalentBasic::query()->updateOrCreate(
                ['snapshot_id' => $snapshotId],
                $basicArray
            ),
            ResourceType::GROUP => WikiSnapshotGroupBasic::query()->updateOrCreate(
                ['snapshot_id' => $snapshotId],
                $basicArray
            ),
            ResourceType::AGENCY => WikiSnapshotAgencyBasic::query()->updateOrCreate(
                ['snapshot_id' => $snapshotId],
                $basicArray
            ),
            ResourceType::SONG => WikiSnapshotSongBasic::query()->updateOrCreate(
                ['snapshot_id' => $snapshotId],
                $basicArray
            ),
            ResourceType::IMAGE => throw new InvalidArgumentException('IMAGE resource type does not have a Basic.'),
        };
    }

    private function toDomainEntity(WikiSnapshotModel $model): WikiSnapshot
    {
        $resourceType = ResourceType::from($model->resource_type);
        $basic = $this->buildBasicFromModel($model, $resourceType);

        return new WikiSnapshot(
            new WikiSnapshotIdentifier($model->id),
            new WikiIdentifier($model->wiki_id),
            new TranslationSetIdentifier($model->translation_set_identifier),
            new Slug($model->slug),
            Language::from($model->language),
            $resourceType,
            $basic,
            SectionContentMapper::collectionFromArray($model->sections, 1),
            $model->theme_color ? new Color($model->theme_color) : null,
            new Version($model->version),
            $model->editor_id ? new PrincipalIdentifier($model->editor_id) : null,
            $model->approver_id ? new PrincipalIdentifier($model->approver_id) : null,
            $model->merger_id ? new PrincipalIdentifier($model->merger_id) : null,
            $model->source_editor_id ? new PrincipalIdentifier($model->source_editor_id) : null,
            $model->merged_at?->toDateTimeImmutable(),
            $model->translated_at?->toDateTimeImmutable(),
            $model->approved_at?->toDateTimeImmutable(),
            $model->created_at->toDateTimeImmutable(),
        );
    }

    private function buildBasicFromModel(WikiSnapshotModel $model, ResourceType $resourceType): BasicInterface
    {
        return match ($resourceType) {
            ResourceType::TALENT => $this->buildTalentBasic($model),
            ResourceType::GROUP => $this->buildGroupBasic($model),
            ResourceType::AGENCY => $this->buildAgencyBasic($model),
            ResourceType::SONG => $this->buildSongBasic($model),
            ResourceType::IMAGE => throw new InvalidArgumentException('IMAGE resource type does not have a Basic.'),
        };
    }

    private function buildTalentBasic(WikiSnapshotModel $model): TalentBasic
    {
        $basic = $model->talentBasic;
        if ($basic === null) {
            throw new InvalidArgumentException('TalentBasic not found for WikiSnapshot.');
        }

        return TalentBasic::fromArray([
            'name' => $basic->name,
            'normalized_name' => $basic->normalized_name,
            'real_name' => $basic->real_name,
            'normalized_real_name' => $basic->normalized_real_name,
            'birthday' => $basic->birthday,
            'agency_identifier' => $basic->agency_identifier,
            'group_identifiers' => $basic->group_identifiers,
            'emoji' => $basic->emoji,
            'representative_symbol' => $basic->representative_symbol,
            'position' => $basic->position,
            'mbti' => $basic->mbti,
            'zodiac_sign' => $basic->zodiac_sign,
            'english_level' => $basic->english_level,
            'height' => $basic->height,
            'blood_type' => $basic->blood_type,
            'fandom_name' => $basic->fandom_name,
            'profile_image_identifier' => $basic->profile_image_identifier,
        ]);
    }

    private function buildGroupBasic(WikiSnapshotModel $model): GroupBasic
    {
        $basic = $model->groupBasic;
        if ($basic === null) {
            throw new InvalidArgumentException('GroupBasic not found for WikiSnapshot.');
        }

        return GroupBasic::fromArray([
            'name' => $basic->name,
            'normalized_name' => $basic->normalized_name,
            'agency_identifier' => $basic->agency_identifier,
            'group_type' => $basic->group_type,
            'status' => $basic->status,
            'generation' => $basic->generation,
            'debut_date' => $basic->debut_date,
            'disband_date' => $basic->disband_date,
            'fandom_name' => $basic->fandom_name,
            'official_colors' => $basic->official_colors,
            'emoji' => $basic->emoji,
            'representative_symbol' => $basic->representative_symbol,
            'main_image_identifier' => $basic->main_image_identifier,
        ]);
    }

    private function buildAgencyBasic(WikiSnapshotModel $model): AgencyBasic
    {
        $basic = $model->agencyBasic;
        if ($basic === null) {
            throw new InvalidArgumentException('AgencyBasic not found for WikiSnapshot.');
        }

        return AgencyBasic::fromArray([
            'name' => $basic->name,
            'normalized_name' => $basic->normalized_name,
            'ceo' => $basic->ceo,
            'normalized_ceo' => $basic->normalized_ceo,
            'founded_in' => $basic->founded_in,
            'parent_agency_identifier' => $basic->parent_agency_identifier,
            'status' => $basic->status,
            'logo_image_identifier' => $basic->logo_image_identifier,
            'official_website' => $basic->official_website,
            'social_links' => $basic->social_links,
        ]);
    }

    private function buildSongBasic(WikiSnapshotModel $model): SongBasic
    {
        $basic = $model->songBasic;
        if ($basic === null) {
            throw new InvalidArgumentException('SongBasic not found for WikiSnapshot.');
        }

        return SongBasic::fromArray([
            'name' => $basic->name,
            'normalized_name' => $basic->normalized_name,
            'song_type' => $basic->song_type,
            'genres' => $basic->genres,
            'agency_identifier' => $basic->agency_identifier,
            'group_identifiers' => $basic->group_identifiers,
            'talent_identifiers' => $basic->talent_identifiers,
            'release_date' => $basic->release_date,
            'album_name' => $basic->album_name,
            'cover_image_identifier' => $basic->cover_image_identifier,
            'lyricist' => $basic->lyricist,
            'normalized_lyricist' => $basic->normalized_lyricist,
            'composer' => $basic->composer,
            'normalized_composer' => $basic->normalized_composer,
            'arranger' => $basic->arranger,
            'normalized_arranger' => $basic->normalized_arranger,
        ]);
    }
}
