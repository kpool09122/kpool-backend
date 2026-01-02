<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Infrastructure\Adapters\Repository;

use Application\Models\Wiki\Group;
use Application\Models\Wiki\Song as SongModel;
use Application\Models\Wiki\Talent;
use Source\Shared\Domain\ValueObject\ExternalContentLink;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\GroupIdentifier;
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
            Language::from($songModel->language),
            new SongName($songModel->name),
            $songModel->agency_id ? new AgencyIdentifier($songModel->agency_id) : null,
            $groupIdentifier,
            $talentIdentifier,
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
        $releaseDate = $song->releaseDate();
        $releaseDateValue = $releaseDate?->format('Y-m-d');

        /** @var SongModel $songModel */
        $songModel = SongModel::query()->updateOrCreate(
            [
                'id' => (string) $song->songIdentifier(),
            ],
            [
                'translation_set_identifier' => (string) $song->translationSetIdentifier(),
                'language' => $song->language()->value,
                'name' => (string) $song->name(),
                'agency_id' => $song->agencyIdentifier() ? (string) $song->agencyIdentifier() : null,
                'lyricist' => (string) $song->lyricist(),
                'composer' => (string) $song->composer(),
                'release_date' => $releaseDateValue,
                'overview' => (string) $song->overView(),
                'cover_image_path' => $song->coverImagePath() ? (string) $song->coverImagePath() : null,
                'music_video_link' => $song->musicVideoLink() ? (string) $song->musicVideoLink() : null,
                'version' => $song->version()->value(),
            ],
        );

        $groupId = $song->groupIdentifier() ? [(string) $song->groupIdentifier()] : [];
        $songModel->groups()->sync($groupId);

        $talentId = $song->talentIdentifier() ? [(string) $song->talentIdentifier()] : [];
        $songModel->talents()->sync($talentId);
    }
}
