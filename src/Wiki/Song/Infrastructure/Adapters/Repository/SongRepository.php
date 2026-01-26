<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Infrastructure\Adapters\Repository;

use Application\Models\Wiki\Group;
use Application\Models\Wiki\Song as SongModel;
use Application\Models\Wiki\Talent;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Shared\Domain\ValueObject\TalentIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Song\Domain\Entity\Song;
use Source\Wiki\Song\Domain\Repository\SongRepositoryInterface;
use Source\Wiki\Song\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Song\Domain\ValueObject\Composer;
use Source\Wiki\Song\Domain\ValueObject\Lyricist;
use Source\Wiki\Song\Domain\ValueObject\Overview;
use Source\Wiki\Song\Domain\ValueObject\ReleaseDate;
use Source\Wiki\Song\Domain\ValueObject\SongIdentifier;
use Source\Wiki\Song\Domain\ValueObject\SongName;

final class SongRepository implements SongRepositoryInterface
{
    public function findById(SongIdentifier $songIdentifier): ?Song
    {
        $songModel = SongModel::query()
            ->with(['groups', 'talents'])
            ->where('id', (string) $songIdentifier)
            ->first();

        if ($songModel === null) {
            return null;
        }

        return $this->toEntity($songModel);
    }

    public function existsBySlug(Slug $slug): bool
    {
        return SongModel::query()
            ->where('slug', (string) $slug)
            ->exists();
    }

    /**
     * @return Song[]
     */
    public function findByTranslationSetIdentifier(TranslationSetIdentifier $translationSetIdentifier): array
    {
        $songModels = SongModel::query()
            ->with(['groups', 'talents'])
            ->where('translation_set_identifier', (string) $translationSetIdentifier)
            ->whereNotNull('version')
            ->get();

        return $songModels->map(fn (SongModel $model) => $this->toEntity($model))->toArray();
    }

    public function save(Song $song): void
    {
        $releaseDate = $song->releaseDate();
        $releaseDateValue = $releaseDate?->format('Y-m-d');

        /** @var SongModel $songModel */
        $songModel = SongModel::query()->updateOrCreate(
            [
                'id' => (string) $song->songIdentifier(),
            ],
            [
                'translation_set_identifier' => (string) $song->translationSetIdentifier(),
                'slug' => (string) $song->slug(),
                'language' => $song->language()->value,
                'name' => (string) $song->name(),
                'agency_id' => $song->agencyIdentifier() ? (string) $song->agencyIdentifier() : null,
                'lyricist' => (string) $song->lyricist(),
                'composer' => (string) $song->composer(),
                'release_date' => $releaseDateValue,
                'overview' => (string) $song->overView(),
                'editor_id' => $song->editorIdentifier() ? (string) $song->editorIdentifier() : null,
                'approver_id' => $song->approverIdentifier() ? (string) $song->approverIdentifier() : null,
                'merger_id' => $song->mergerIdentifier() ? (string) $song->mergerIdentifier() : null,
                'merged_at' => $song->mergedAt(),
                'version' => $song->version()->value(),
                'is_official' => $song->isOfficial(),
                'owner_account_id' => $song->ownerAccountIdentifier() ? (string) $song->ownerAccountIdentifier() : null,
            ],
        );

        $groupId = $song->groupIdentifier() ? [(string) $song->groupIdentifier()] : [];
        $songModel->groups()->sync($groupId);

        $talentId = $song->talentIdentifier() ? [(string) $song->talentIdentifier()] : [];
        $songModel->talents()->sync($talentId);
    }

    private function toEntity(SongModel $songModel): Song
    {
        /** @var Group|null $group */
        $group = $songModel->groups->first();
        $groupIdentifier = $group ? new GroupIdentifier($group->id) : null;

        /** @var Talent|null $talent */
        $talent = $songModel->talents->first();
        $talentIdentifier = $talent ? new TalentIdentifier($talent->id) : null;

        $releaseDate = $songModel->release_date
            ? new ReleaseDate($songModel->release_date->toDateTimeImmutable())
            : null;

        return new Song(
            new SongIdentifier($songModel->id),
            new TranslationSetIdentifier($songModel->translation_set_identifier),
            new Slug($songModel->slug),
            Language::from($songModel->language),
            new SongName($songModel->name),
            $songModel->agency_id ? new AgencyIdentifier($songModel->agency_id) : null,
            $groupIdentifier,
            $talentIdentifier,
            new Lyricist($songModel->lyricist),
            new Composer($songModel->composer),
            $releaseDate,
            new Overview($songModel->overview),
            new Version($songModel->version),
            $songModel->merger_id ? new PrincipalIdentifier($songModel->merger_id) : null,
            $songModel->merged_at?->toDateTimeImmutable(),
            $songModel->editor_id ? new PrincipalIdentifier($songModel->editor_id) : null,
            $songModel->approver_id ? new PrincipalIdentifier($songModel->approver_id) : null,
            (bool) $songModel->is_official,
            $songModel->owner_account_id ? new AccountIdentifier($songModel->owner_account_id) : null,
        );
    }
}
