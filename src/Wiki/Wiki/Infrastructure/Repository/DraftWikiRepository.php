<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Infrastructure\Repository;

use Application\Models\Wiki\DraftWiki as DraftWikiModel;
use Application\Models\Wiki\DraftWikiAgencyBasic;
use Application\Models\Wiki\DraftWikiGroupBasic;
use Application\Models\Wiki\DraftWikiSongBasic;
use Application\Models\Wiki\DraftWikiTalentBasic;
use InvalidArgumentException;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Wiki\Domain\Entity\DraftWiki;
use Source\Wiki\Wiki\Domain\Repository\DraftWikiRepositoryInterface;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Agency\AgencyBasic;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Group\GroupBasic;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\BasicInterface;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Song\SongBasic;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Talent\TalentBasic;
use Source\Wiki\Wiki\Domain\ValueObject\Color;
use Source\Wiki\Wiki\Domain\ValueObject\DraftWikiIdentifier;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;

readonly class DraftWikiRepository implements DraftWikiRepositoryInterface
{
    public function findById(DraftWikiIdentifier $wikiIdentifier): ?DraftWiki
    {
        $model = DraftWikiModel::query()
            ->with(['talentBasic.groups', 'groupBasic', 'agencyBasic', 'songBasic.groups', 'songBasic.talents'])
            ->where('id', (string) $wikiIdentifier)
            ->first();

        if ($model === null) {
            return null;
        }

        return $this->toDomainEntity($model);
    }

    public function findBySlugAndLanguage(Slug $slug, Language $language): ?DraftWiki
    {
        $model = DraftWikiModel::query()
            ->with(['talentBasic.groups', 'groupBasic', 'agencyBasic', 'songBasic.groups', 'songBasic.talents'])
            ->where('slug', (string) $slug)
            ->where('language', $language->value)
            ->first();

        if ($model === null) {
            return null;
        }

        return $this->toDomainEntity($model);
    }

    public function findByPublishedWikiIdentifier(WikiIdentifier $wikiIdentifier): ?DraftWiki
    {
        $model = DraftWikiModel::query()
            ->with(['talentBasic.groups', 'groupBasic', 'agencyBasic', 'songBasic.groups', 'songBasic.talents'])
            ->where('published_wiki_id', (string) $wikiIdentifier)
            ->first();

        if ($model === null) {
            return null;
        }

        return $this->toDomainEntity($model);
    }

    /**
     * @return DraftWiki[]
     */
    public function findByTranslationSetIdentifier(TranslationSetIdentifier $translationSetIdentifier): array
    {
        $models = DraftWikiModel::query()
            ->with(['talentBasic.groups', 'groupBasic', 'agencyBasic', 'songBasic.groups', 'songBasic.talents'])
            ->where('translation_set_identifier', (string) $translationSetIdentifier)
            ->get();

        return $models->map(fn (DraftWikiModel $model) => $this->toDomainEntity($model))->toArray();
    }

    /**
     * @return DraftWiki[]
     */
    public function findByEditorIdentifier(PrincipalIdentifier $editorIdentifier, int $limit = 20, int $offset = 0): array
    {
        $models = DraftWikiModel::query()
            ->with(['talentBasic.groups', 'groupBasic', 'agencyBasic', 'songBasic.groups', 'songBasic.talents'])
            ->where('editor_id', (string) $editorIdentifier)
            ->orderBy('created_at', 'desc')
            ->skip($offset)
            ->take($limit)
            ->get();

        return $models->map(fn (DraftWikiModel $model) => $this->toDomainEntity($model))->toArray();
    }

    /**
     * @return DraftWiki[]
     */
    public function findByStatus(ApprovalStatus $status, int $limit = 20, int $offset = 0): array
    {
        $models = DraftWikiModel::query()
            ->with(['talentBasic.groups', 'groupBasic', 'agencyBasic', 'songBasic.groups', 'songBasic.talents'])
            ->where('status', $status->value)
            ->orderBy('created_at', 'desc')
            ->skip($offset)
            ->take($limit)
            ->get();

        return $models->map(fn (DraftWikiModel $model) => $this->toDomainEntity($model))->toArray();
    }

    /**
     * @return DraftWiki[]
     */
    public function findByResourceType(ResourceType $resourceType, int $limit = 20, int $offset = 0): array
    {
        $models = DraftWikiModel::query()
            ->with(['talentBasic.groups', 'groupBasic', 'agencyBasic', 'songBasic.groups', 'songBasic.talents'])
            ->where('resource_type', $resourceType->value)
            ->orderBy('created_at', 'desc')
            ->skip($offset)
            ->take($limit)
            ->get();

        return $models->map(fn (DraftWikiModel $model) => $this->toDomainEntity($model))->toArray();
    }

    public function save(DraftWiki $draftWiki): void
    {
        $wikiId = (string) $draftWiki->wikiIdentifier();

        DraftWikiModel::query()->updateOrCreate(
            [
                'id' => $wikiId,
            ],
            [
                'published_wiki_id' => $draftWiki->publishedWikiIdentifier()
                    ? (string) $draftWiki->publishedWikiIdentifier()
                    : null,
                'translation_set_identifier' => (string) $draftWiki->translationSetIdentifier(),
                'slug' => (string) $draftWiki->slug(),
                'language' => $draftWiki->language()->value,
                'resource_type' => $draftWiki->resourceType()->value,
                'sections' => SectionContentMapper::collectionToArray($draftWiki->sections()),
                'theme_color' => $draftWiki->themeColor() ? (string) $draftWiki->themeColor() : null,
                'status' => $draftWiki->status()->value,
                'editor_id' => $draftWiki->editorIdentifier() ? (string) $draftWiki->editorIdentifier() : null,
                'approver_id' => $draftWiki->approverIdentifier() ? (string) $draftWiki->approverIdentifier() : null,
                'merger_id' => $draftWiki->mergerIdentifier() ? (string) $draftWiki->mergerIdentifier() : null,
                'merged_at' => $draftWiki->mergedAt(),
                'source_editor_id' => $draftWiki->sourceEditorIdentifier()
                    ? (string) $draftWiki->sourceEditorIdentifier()
                    : null,
                'translated_at' => $draftWiki->translatedAt(),
                'approved_at' => $draftWiki->approvedAt(),
            ]
        );

        $this->saveBasic($wikiId, $draftWiki->resourceType(), $draftWiki->basic());
    }

    public function delete(DraftWiki $draftWiki): void
    {
        DraftWikiModel::query()
            ->where('id', (string) $draftWiki->wikiIdentifier())
            ->delete();
    }

    private function saveBasic(string $wikiId, ResourceType $resourceType, BasicInterface $basic): void
    {
        $basicArray = $basic->toArray();
        unset($basicArray['type']);

        $groupIdentifiers = $basicArray['group_identifiers'] ?? null;
        unset($basicArray['group_identifiers']);
        $talentIdentifiers = $basicArray['talent_identifiers'] ?? null;
        unset($basicArray['talent_identifiers']);

        match ($resourceType) {
            ResourceType::TALENT => (function () use ($wikiId, $basicArray, $groupIdentifiers) {
                DraftWikiTalentBasic::query()->updateOrCreate(['wiki_id' => $wikiId], $basicArray);
                $talentBasic = DraftWikiTalentBasic::query()->where('wiki_id', $wikiId)->first();
                $talentBasic->groups()->sync($groupIdentifiers ?? []);
            })(),
            ResourceType::GROUP => DraftWikiGroupBasic::query()->updateOrCreate(
                ['wiki_id' => $wikiId],
                $basicArray
            ),
            ResourceType::AGENCY => DraftWikiAgencyBasic::query()->updateOrCreate(
                ['wiki_id' => $wikiId],
                $basicArray
            ),
            ResourceType::SONG => (function () use ($wikiId, $basicArray, $groupIdentifiers, $talentIdentifiers) {
                DraftWikiSongBasic::query()->updateOrCreate(['wiki_id' => $wikiId], $basicArray);
                $songBasic = DraftWikiSongBasic::query()->where('wiki_id', $wikiId)->first();
                $songBasic->groups()->sync($groupIdentifiers ?? []);
                $songBasic->talents()->sync($talentIdentifiers ?? []);
            })(),
            ResourceType::IMAGE => throw new InvalidArgumentException('IMAGE resource type does not have a Basic.'),
        };
    }

    private function toDomainEntity(DraftWikiModel $model): DraftWiki
    {
        $resourceType = ResourceType::from($model->resource_type);
        $basic = match ($resourceType) {
            ResourceType::TALENT => $this->buildTalentBasic($model),
            ResourceType::GROUP => $this->buildGroupBasic($model),
            ResourceType::AGENCY => $this->buildAgencyBasic($model),
            ResourceType::SONG => $this->buildSongBasic($model),
            ResourceType::IMAGE => throw new InvalidArgumentException('IMAGE resource type does not have a Basic.'),
        };

        return new DraftWiki(
            new DraftWikiIdentifier($model->id),
            $model->published_wiki_id ? new WikiIdentifier($model->published_wiki_id) : null,
            new TranslationSetIdentifier($model->translation_set_identifier),
            new Slug($model->slug),
            Language::from($model->language),
            $resourceType,
            $basic,
            SectionContentMapper::collectionFromArray($model->sections, 1),
            $model->theme_color ? new Color($model->theme_color) : null,
            ApprovalStatus::from($model->status),
            $model->editor_id ? new PrincipalIdentifier($model->editor_id) : null,
            $model->approver_id ? new PrincipalIdentifier($model->approver_id) : null,
            $model->merger_id ? new PrincipalIdentifier($model->merger_id) : null,
            $model->source_editor_id ? new PrincipalIdentifier($model->source_editor_id) : null,
            $model->merged_at?->toDateTimeImmutable(),
            $model->translated_at?->toDateTimeImmutable(),
            $model->approved_at?->toDateTimeImmutable(),
        );
    }

    private function buildTalentBasic(DraftWikiModel $model): TalentBasic
    {
        $basic = $model->talentBasic;
        if ($basic === null) {
            throw new InvalidArgumentException('TalentBasic not found for DraftWiki.');
        }

        return TalentBasic::fromArray([
            'name' => $basic->name,
            'normalized_name' => $basic->normalized_name,
            'real_name' => $basic->real_name,
            'normalized_real_name' => $basic->normalized_real_name,
            'birthday' => $basic->birthday,
            'agency_identifier' => $basic->agency_identifier,
            'group_identifiers' => $basic->groups->pluck('id')->toArray(),
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

    private function buildGroupBasic(DraftWikiModel $model): GroupBasic
    {
        $basic = $model->groupBasic;
        if ($basic === null) {
            throw new InvalidArgumentException('GroupBasic not found for DraftWiki.');
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

    private function buildAgencyBasic(DraftWikiModel $model): AgencyBasic
    {
        $basic = $model->agencyBasic;
        if ($basic === null) {
            throw new InvalidArgumentException('AgencyBasic not found for DraftWiki.');
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

    private function buildSongBasic(DraftWikiModel $model): SongBasic
    {
        $basic = $model->songBasic;
        if ($basic === null) {
            throw new InvalidArgumentException('SongBasic not found for DraftWiki.');
        }

        return SongBasic::fromArray([
            'name' => $basic->name,
            'normalized_name' => $basic->normalized_name,
            'song_type' => $basic->song_type,
            'genres' => $basic->genres,
            'agency_identifier' => $basic->agency_identifier,
            'group_identifiers' => $basic->groups->pluck('id')->toArray(),
            'talent_identifiers' => $basic->talents->pluck('id')->toArray(),
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
