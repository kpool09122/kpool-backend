<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Infrastracture\Adapters\Repository;

use Application\Models\Wiki\DraftSong as DraftSongModel;
use Application\Models\Wiki\Song as SongModel;
use DateTimeImmutable;
use DateTimeInterface;
use Source\Shared\Domain\ValueObject\ExternalContentLink;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Song\Domain\Entity\DraftSong;
use Source\Wiki\Song\Domain\Entity\Song;
use Source\Wiki\Song\Domain\Repository\SongRepositoryInterface;
use Source\Wiki\Song\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Song\Domain\ValueObject\BelongIdentifier;
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
            ->where('id', (string) $songIdentifier)
            ->first();

        if ($songModel === null) {
            return null;
        }

        $belongIdentifiers = [];
        foreach (($songModel->belong_identifiers ?? []) as $identifier) {
            $belongIdentifiers[] = new BelongIdentifier($identifier);
        }

        $releaseDate = $this->createReleaseDate($songModel->release_date);

        return new Song(
            new SongIdentifier($songModel->id),
            new TranslationSetIdentifier($songModel->translation_set_identifier),
            Language::from($songModel->language),
            new SongName($songModel->name),
            $songModel->agency_id ? new AgencyIdentifier($songModel->agency_id) : null,
            $belongIdentifiers,
            new Lyricist($songModel->lyricist),
            new Composer($songModel->composer),
            $releaseDate,
            new Overview($songModel->overview),
            $songModel->cover_image_path ? new ImagePath($songModel->cover_image_path) : null,
            $songModel->music_video_link ? new ExternalContentLink($songModel->music_video_link) : null,
            new Version($songModel->version ?? 1),
        );
    }

    public function save(Song $song): void
    {
        $belongIdentifiers = [];
        foreach ($song->belongIdentifiers() as $identifier) {
            $belongIdentifiers[] = (string) $identifier;
        }

        $releaseDate = $song->releaseDate();
        $releaseDateValue = $releaseDate?->format('Y-m-d');

        SongModel::query()->updateOrCreate(
            [
                'id' => (string) $song->songIdentifier(),
            ],
            [
                'translation_set_identifier' => (string) $song->translationSetIdentifier(),
                'language' => $song->language()->value,
                'name' => (string) $song->name(),
                'agency_id' => $song->agencyIdentifier() ? (string) $song->agencyIdentifier() : null,
                'belong_identifiers' => $belongIdentifiers,
                'lyricist' => (string) $song->lyricist(),
                'composer' => (string) $song->composer(),
                'release_date' => $releaseDateValue,
                'overview' => (string) $song->overView(),
                'cover_image_path' => $song->coverImagePath() ? (string) $song->coverImagePath() : null,
                'music_video_link' => $song->musicVideoLink() ? (string) $song->musicVideoLink() : null,
                'version' => $song->version()->value(),
            ],
        );
    }

    public function findDraftById(SongIdentifier $songIdentifier): ?DraftSong
    {
        $draftModel = DraftSongModel::query()
            ->where('id', (string) $songIdentifier)
            ->first();

        if ($draftModel === null) {
            return null;
        }

        $belongIdentifiers = [];
        foreach (($draftModel->belong_identifiers ?? []) as $identifier) {
            $belongIdentifiers[] = new BelongIdentifier($identifier);
        }

        $releaseDate = $this->createReleaseDate($draftModel->release_date);

        return new DraftSong(
            new SongIdentifier($draftModel->id),
            $draftModel->published_id ? new SongIdentifier($draftModel->published_id) : null,
            new TranslationSetIdentifier($draftModel->translation_set_identifier),
            new EditorIdentifier($draftModel->editor_id),
            Language::from($draftModel->language),
            new SongName($draftModel->name),
            $draftModel->agency_id ? new AgencyIdentifier($draftModel->agency_id) : null,
            $belongIdentifiers,
            new Lyricist($draftModel->lyricist),
            new Composer($draftModel->composer),
            $releaseDate,
            new Overview($draftModel->overview),
            $draftModel->cover_image_path ? new ImagePath($draftModel->cover_image_path) : null,
            $draftModel->music_video_link ? new ExternalContentLink($draftModel->music_video_link) : null,
            ApprovalStatus::from($draftModel->status),
        );
    }

    public function saveDraft(DraftSong $song): void
    {
        $belongIdentifiers = [];
        foreach ($song->belongIdentifiers() as $identifier) {
            $belongIdentifiers[] = (string) $identifier;
        }

        $releaseDate = $song->releaseDate();
        $releaseDateValue = $releaseDate?->format('Y-m-d');

        DraftSongModel::query()->updateOrCreate(
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
                'agency_id' => $song->agencyIdentifier() ? (string) $song->agencyIdentifier() : null,
                'belong_identifiers' => $belongIdentifiers,
                'lyricist' => (string) $song->lyricist(),
                'composer' => (string) $song->composer(),
                'release_date' => $releaseDateValue,
                'overview' => (string) $song->overView(),
                'cover_image_path' => $song->coverImagePath() ? (string) $song->coverImagePath() : null,
                'music_video_link' => $song->musicVideoLink() ? (string) $song->musicVideoLink() : null,
                'status' => $song->status()->value,
            ],
        );
    }

    public function deleteDraft(DraftSong $song): void
    {
        DraftSongModel::query()
            ->where('id', (string) $song->songIdentifier())
            ->delete();
    }

    public function findDraftsByTranslationSet(
        TranslationSetIdentifier $translationSetIdentifier,
    ): array {
        $draftModels = DraftSongModel::query()
            ->where('translation_set_identifier', (string) $translationSetIdentifier)
            ->get();

        $drafts = [];

        foreach ($draftModels as $model) {
            $belongIdentifiers = [];
            foreach (($model->belong_identifiers ?? []) as $identifier) {
                $belongIdentifiers[] = new BelongIdentifier($identifier);
            }

            $releaseDate = $this->createReleaseDate($model->release_date);

            $drafts[] = new DraftSong(
                new SongIdentifier($model->id),
                $model->published_id ? new SongIdentifier($model->published_id) : null,
                new TranslationSetIdentifier($model->translation_set_identifier),
                new EditorIdentifier($model->editor_id),
                Language::from($model->language),
                new SongName($model->name),
                $model->agency_id ? new AgencyIdentifier($model->agency_id) : null,
                $belongIdentifiers,
                new Lyricist($model->lyricist),
                new Composer($model->composer),
                $releaseDate,
                new Overview($model->overview),
                $model->cover_image_path ? new ImagePath($model->cover_image_path) : null,
                $model->music_video_link ? new ExternalContentLink($model->music_video_link) : null,
                ApprovalStatus::from($model->status),
            );
        }

        return $drafts;
    }

    private function createReleaseDate(?DateTimeInterface $releaseDateValue): ?ReleaseDate
    {
        if ($releaseDateValue === null) {
            return null;
        }

        $immutableReleaseDate = $releaseDateValue instanceof DateTimeImmutable
            ? $releaseDateValue
            : DateTimeImmutable::createFromInterface($releaseDateValue);

        return new ReleaseDate($immutableReleaseDate);
    }
}
