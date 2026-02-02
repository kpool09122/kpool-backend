<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Infrastructure\Repository;

use Application\Models\Wiki\Wiki as WikiModel;
use Application\Models\Wiki\WikiAgencyBasic;
use Application\Models\Wiki\WikiGroupBasic;
use Application\Models\Wiki\WikiSongBasic;
use Application\Models\Wiki\WikiTalentBasic;
use InvalidArgumentException;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Wiki\Domain\Entity\Wiki;
use Source\Wiki\Wiki\Domain\Repository\WikiRepositoryInterface;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Agency\AgencyBasic;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Group\GroupBasic;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\BasicInterface;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Song\SongBasic;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Talent\TalentBasic;
use Source\Wiki\Wiki\Domain\ValueObject\Color;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;

readonly class WikiRepository implements WikiRepositoryInterface
{
    public function findById(WikiIdentifier $wikiIdentifier): ?Wiki
    {
        $model = WikiModel::query()
            ->with(['talentBasic', 'groupBasic', 'agencyBasic', 'songBasic'])
            ->where('id', (string) $wikiIdentifier)
            ->first();

        if ($model === null) {
            return null;
        }

        return $this->toDomainEntity($model);
    }

    public function findBySlugAndLanguage(Slug $slug, Language $language): ?Wiki
    {
        $model = WikiModel::query()
            ->with(['talentBasic', 'groupBasic', 'agencyBasic', 'songBasic'])
            ->where('slug', (string) $slug)
            ->where('language', $language->value)
            ->first();

        if ($model === null) {
            return null;
        }

        return $this->toDomainEntity($model);
    }

    public function existsBySlug(Slug $slug): bool
    {
        return WikiModel::query()
            ->where('slug', (string) $slug)
            ->exists();
    }

    /**
     * @return Wiki[]
     */
    public function findByTranslationSetIdentifier(TranslationSetIdentifier $translationSetIdentifier): array
    {
        $models = WikiModel::query()
            ->with(['talentBasic', 'groupBasic', 'agencyBasic', 'songBasic'])
            ->where('translation_set_identifier', (string) $translationSetIdentifier)
            ->get();

        return $models->map(fn (WikiModel $model) => $this->toDomainEntity($model))->toArray();
    }

    /**
     * @return Wiki[]
     */
    public function findByResourceType(ResourceType $resourceType, int $limit = 20, int $offset = 0): array
    {
        $models = WikiModel::query()
            ->with(['talentBasic', 'groupBasic', 'agencyBasic', 'songBasic'])
            ->where('resource_type', $resourceType->value)
            ->orderBy('created_at', 'desc')
            ->skip($offset)
            ->take($limit)
            ->get();

        return $models->map(fn (WikiModel $model) => $this->toDomainEntity($model))->toArray();
    }

    public function save(Wiki $wiki): void
    {
        $wikiId = (string) $wiki->wikiIdentifier();

        WikiModel::query()->updateOrCreate(
            [
                'id' => $wikiId,
            ],
            [
                'translation_set_identifier' => (string) $wiki->translationSetIdentifier(),
                'slug' => (string) $wiki->slug(),
                'language' => $wiki->language()->value,
                'resource_type' => $wiki->resourceType()->value,
                'sections' => SectionContentMapper::collectionToArray($wiki->sections()),
                'theme_color' => $wiki->themeColor() ? (string) $wiki->themeColor() : null,
                'version' => $wiki->version()->value(),
                'editor_id' => $wiki->editorIdentifier() ? (string) $wiki->editorIdentifier() : null,
                'approver_id' => $wiki->approverIdentifier() ? (string) $wiki->approverIdentifier() : null,
                'merger_id' => $wiki->mergerIdentifier() ? (string) $wiki->mergerIdentifier() : null,
                'merged_at' => $wiki->mergedAt(),
                'owner_account_id' => $wiki->ownerAccountIdentifier() ? (string) $wiki->ownerAccountIdentifier() : null,
                'source_editor_id' => $wiki->sourceEditorIdentifier() ? (string) $wiki->sourceEditorIdentifier() : null,
                'translated_at' => $wiki->translatedAt(),
                'approved_at' => $wiki->approvedAt(),
            ]
        );

        $this->saveBasic($wikiId, $wiki->resourceType(), $wiki->basic());
    }

    public function delete(Wiki $wiki): void
    {
        WikiModel::query()
            ->where('id', (string) $wiki->wikiIdentifier())
            ->delete();
    }

    private function saveBasic(string $wikiId, ResourceType $resourceType, BasicInterface $basic): void
    {
        $basicArray = $basic->toArray();
        unset($basicArray['type']);

        match ($resourceType) {
            ResourceType::TALENT => WikiTalentBasic::query()->updateOrCreate(
                ['wiki_id' => $wikiId],
                $basicArray
            ),
            ResourceType::GROUP => WikiGroupBasic::query()->updateOrCreate(
                ['wiki_id' => $wikiId],
                $basicArray
            ),
            ResourceType::AGENCY => WikiAgencyBasic::query()->updateOrCreate(
                ['wiki_id' => $wikiId],
                $basicArray
            ),
            ResourceType::SONG => WikiSongBasic::query()->updateOrCreate(
                ['wiki_id' => $wikiId],
                $basicArray
            ),
            ResourceType::IMAGE => throw new InvalidArgumentException('IMAGE resource type does not have a Basic.'),
        };
    }

    private function toDomainEntity(WikiModel $model): Wiki
    {
        $resourceType = ResourceType::from($model->resource_type);
        $basic = $this->buildBasicFromModel($model, $resourceType);

        return new Wiki(
            new WikiIdentifier($model->id),
            new TranslationSetIdentifier($model->translation_set_identifier),
            new Slug($model->slug),
            Language::from($model->language),
            $resourceType,
            $basic,
            SectionContentMapper::collectionFromArray($model->sections, 1),
            $model->theme_color ? new Color($model->theme_color) : null,
            new Version($model->version),
            $model->owner_account_id ? new AccountIdentifier($model->owner_account_id) : null,
            $model->editor_id ? new PrincipalIdentifier($model->editor_id) : null,
            $model->approver_id ? new PrincipalIdentifier($model->approver_id) : null,
            $model->merger_id ? new PrincipalIdentifier($model->merger_id) : null,
            $model->source_editor_id ? new PrincipalIdentifier($model->source_editor_id) : null,
            $model->merged_at?->toDateTimeImmutable(),
            $model->translated_at?->toDateTimeImmutable(),
            $model->approved_at?->toDateTimeImmutable(),
        );
    }

    private function buildBasicFromModel(WikiModel $model, ResourceType $resourceType): BasicInterface
    {
        return match ($resourceType) {
            ResourceType::TALENT => $this->buildTalentBasic($model),
            ResourceType::GROUP => $this->buildGroupBasic($model),
            ResourceType::AGENCY => $this->buildAgencyBasic($model),
            ResourceType::SONG => $this->buildSongBasic($model),
            ResourceType::IMAGE => throw new InvalidArgumentException('IMAGE resource type does not have a Basic.'),
        };
    }

    private function buildTalentBasic(WikiModel $model): TalentBasic
    {
        $basic = $model->talentBasic;
        if ($basic === null) {
            throw new InvalidArgumentException('TalentBasic not found for Wiki.');
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

    private function buildGroupBasic(WikiModel $model): GroupBasic
    {
        $basic = $model->groupBasic;
        if ($basic === null) {
            throw new InvalidArgumentException('GroupBasic not found for Wiki.');
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

    private function buildAgencyBasic(WikiModel $model): AgencyBasic
    {
        $basic = $model->agencyBasic;
        if ($basic === null) {
            throw new InvalidArgumentException('AgencyBasic not found for Wiki.');
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

    private function buildSongBasic(WikiModel $model): SongBasic
    {
        $basic = $model->songBasic;
        if ($basic === null) {
            throw new InvalidArgumentException('SongBasic not found for Wiki.');
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
