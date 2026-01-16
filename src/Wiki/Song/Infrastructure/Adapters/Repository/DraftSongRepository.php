<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Infrastructure\Adapters\Repository;

use Application\Models\Wiki\DraftSong as DraftSongModel;
use Source\Shared\Domain\ValueObject\ExternalContentLink;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\TalentIdentifier;
use Source\Wiki\Song\Domain\Entity\DraftSong;
use Source\Wiki\Song\Domain\Repository\DraftSongRepositoryInterface;
use Source\Wiki\Song\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Song\Domain\ValueObject\Composer;
use Source\Wiki\Song\Domain\ValueObject\Lyricist;
use Source\Wiki\Song\Domain\ValueObject\Overview;
use Source\Wiki\Song\Domain\ValueObject\ReleaseDate;
use Source\Wiki\Song\Domain\ValueObject\SongIdentifier;
use Source\Wiki\Song\Domain\ValueObject\SongName;

final class DraftSongRepository implements DraftSongRepositoryInterface
{
    public function findById(SongIdentifier $songIdentifier): ?DraftSong
    {
        $draftModel = DraftSongModel::query()
            ->with(['groups', 'talents'])
            ->where('id', (string) $songIdentifier)
            ->first();

        if ($draftModel === null) {
            return null;
        }

        return $this->toEntity($draftModel);
    }

    public function save(DraftSong $song): void
    {
        $releaseDate = $song->releaseDate();
        $releaseDateValue = $releaseDate?->format('Y-m-d');

        $draftModel = DraftSongModel::query()->updateOrCreate(
            [
                'id' => (string) $song->songIdentifier(),
            ],
            [
                'published_id' => $song->publishedSongIdentifier()
                    ? (string) $song->publishedSongIdentifier()
                    : null,
                'translation_set_identifier' => (string) $song->translationSetIdentifier(),
                'editor_id' => (string) $song->editorIdentifier(),
                'language' => $song->language()->value,
                'name' => (string) $song->name(),
                'normalized_name' => $song->normalizedName(),
                'agency_id' => $song->agencyIdentifier() ? (string) $song->agencyIdentifier() : null,
                'lyricist' => (string) $song->lyricist(),
                'normalized_lyricist' => $song->normalizedLyricist(),
                'composer' => (string) $song->composer(),
                'normalized_composer' => $song->normalizedComposer(),
                'release_date' => $releaseDateValue,
                'overview' => (string) $song->overView(),
                'cover_image_path' => $song->coverImagePath() ? (string) $song->coverImagePath() : null,
                'music_video_link' => $song->musicVideoLink() ? (string) $song->musicVideoLink() : null,
                'status' => $song->status()->value,
            ],
        );

        if (! $draftModel instanceof DraftSongModel) {
            throw new \LogicException('DraftSongModel::query()->updateOrCreate() must return ' . DraftSongModel::class);
        }

        $groupId = $song->groupIdentifier() ? [(string) $song->groupIdentifier()] : [];
        $draftModel->groups()->sync($groupId);

        $talentId = $song->talentIdentifier() ? [(string) $song->talentIdentifier()] : [];
        $draftModel->talents()->sync($talentId);
    }

    public function delete(DraftSong $song): void
    {
        DraftSongModel::query()
            ->where('id', (string) $song->songIdentifier())
            ->delete();
    }

    public function findByTranslationSet(
        TranslationSetIdentifier $translationSetIdentifier,
    ): array {
        $draftModels = DraftSongModel::query()
            ->with(['groups', 'talents'])
            ->where('translation_set_identifier', (string) $translationSetIdentifier)
            ->get();

        return $draftModels
            ->map(fn (DraftSongModel $model): DraftSong => $this->toEntity($model))
            ->toArray();
    }

    private function toEntity(DraftSongModel $draftModel): DraftSong
    {
        $group = $draftModel->groups->first();
        $groupIdentifier = $group ? new GroupIdentifier($group->id) : null;

        $talent = $draftModel->talents->first();
        $talentIdentifier = $talent ? new TalentIdentifier($talent->id) : null;

        $releaseDate = $draftModel->release_date
            ? new ReleaseDate($draftModel->release_date->toDateTimeImmutable())
            : null;

        $normalizedName = $draftModel->getAttribute('normalized_name');
        if (! is_string($normalizedName)) {
            throw new \LogicException('draft_songs.normalized_name must be string');
        }

        $normalizedLyricist = $draftModel->getAttribute('normalized_lyricist');
        if (! is_string($normalizedLyricist)) {
            throw new \LogicException('draft_songs.normalized_lyricist must be string');
        }

        $normalizedComposer = $draftModel->getAttribute('normalized_composer');
        if (! is_string($normalizedComposer)) {
            throw new \LogicException('draft_songs.normalized_composer must be string');
        }

        return new DraftSong(
            new SongIdentifier($draftModel->id),
            $draftModel->published_id ? new SongIdentifier($draftModel->published_id) : null,
            new TranslationSetIdentifier($draftModel->translation_set_identifier),
            new PrincipalIdentifier($draftModel->editor_id),
            Language::from($draftModel->language),
            new SongName($draftModel->name),
            $normalizedName,
            $draftModel->agency_id ? new AgencyIdentifier($draftModel->agency_id) : null,
            $groupIdentifier,
            $talentIdentifier,
            new Lyricist($draftModel->lyricist),
            $normalizedLyricist,
            new Composer($draftModel->composer),
            $normalizedComposer,
            $releaseDate,
            new Overview($draftModel->overview),
            $draftModel->cover_image_path ? new ImagePath($draftModel->cover_image_path) : null,
            $draftModel->music_video_link ? new ExternalContentLink($draftModel->music_video_link) : null,
            ApprovalStatus::from($draftModel->status),
        );
    }
}
