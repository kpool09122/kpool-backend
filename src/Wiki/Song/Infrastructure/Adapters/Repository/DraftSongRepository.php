<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Infrastructure\Adapters\Repository;

use Application\Models\Wiki\DraftSong as DraftSongModel;
use Application\Models\Wiki\Group;
use Application\Models\Wiki\Talent;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Shared\Domain\ValueObject\TalentIdentifier;
use Source\Wiki\Song\Domain\Entity\DraftSong;
use Source\Wiki\Song\Domain\Repository\DraftSongRepositoryInterface;
use Source\Wiki\Song\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Song\Domain\ValueObject\Overview;
use Source\Wiki\Song\Domain\ValueObject\SongIdentifier;
use Source\Wiki\Song\Domain\ValueObject\SongName;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Song\Composer;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Song\Lyricist;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Song\ReleaseDate;

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

        /** @var Group|null $group */
        $group = $draftModel->groups->first();
        $groupIdentifier = $group ? new GroupIdentifier($group->id) : null;

        /** @var Talent|null $talent */
        $talent = $draftModel->talents->first();
        $talentIdentifier = $talent ? new TalentIdentifier($talent->id) : null;

        $releaseDate = $draftModel->release_date
            ? new ReleaseDate($draftModel->release_date->toDateTimeImmutable())
            : null;

        return new DraftSong(
            new SongIdentifier($draftModel->id),
            $draftModel->published_id ? new SongIdentifier($draftModel->published_id) : null,
            new TranslationSetIdentifier($draftModel->translation_set_identifier),
            new Slug($draftModel->slug),
            $draftModel->editor_id ? new PrincipalIdentifier($draftModel->editor_id) : null,
            Language::from($draftModel->language),
            new SongName($draftModel->name),
            $draftModel->agency_id ? new AgencyIdentifier($draftModel->agency_id) : null,
            $groupIdentifier,
            $talentIdentifier,
            new Lyricist($draftModel->lyricist),
            new Composer($draftModel->composer),
            $releaseDate,
            new Overview($draftModel->overview),
            ApprovalStatus::from($draftModel->status),
            $draftModel->approver_id ? new PrincipalIdentifier($draftModel->approver_id) : null,
            $draftModel->merger_id ? new PrincipalIdentifier($draftModel->merger_id) : null,
            null,
            $draftModel->source_editor_id ? new PrincipalIdentifier($draftModel->source_editor_id) : null,
            $draftModel->translated_at?->toDateTimeImmutable(),
            $draftModel->approved_at?->toDateTimeImmutable(),
        );
    }

    public function save(DraftSong $song): void
    {
        $releaseDate = $song->releaseDate();
        $releaseDateValue = $releaseDate?->format('Y-m-d');

        /** @var DraftSongModel $draftModel */
        $draftModel = DraftSongModel::query()->updateOrCreate(
            [
                'id' => (string) $song->songIdentifier(),
            ],
            [
                'published_id' => $song->publishedSongIdentifier()
                    ? (string) $song->publishedSongIdentifier()
                    : null,
                'translation_set_identifier' => (string) $song->translationSetIdentifier(),
                'slug' => (string) $song->slug(),
                'editor_id' => $song->editorIdentifier() ? (string) $song->editorIdentifier() : null,
                'language' => $song->language()->value,
                'name' => (string) $song->name(),
                'agency_id' => $song->agencyIdentifier() ? (string) $song->agencyIdentifier() : null,
                'lyricist' => (string) $song->lyricist(),
                'composer' => (string) $song->composer(),
                'release_date' => $releaseDateValue,
                'overview' => (string) $song->overView(),
                'status' => $song->status()->value,
                'approver_id' => $song->approverIdentifier() ? (string) $song->approverIdentifier() : null,
                'merger_id' => $song->mergerIdentifier() ? (string) $song->mergerIdentifier() : null,
                'source_editor_id' => $song->sourceEditorIdentifier() ? (string) $song->sourceEditorIdentifier() : null,
                'translated_at' => $song->translatedAt(),
                'approved_at' => $song->approvedAt(),
            ],
        );

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

        $drafts = [];

        /** @var DraftSongModel $model */
        foreach ($draftModels as $model) {
            /** @var Group|null $group */
            $group = $model->groups->first();
            $groupIdentifier = $group ? new GroupIdentifier($group->id) : null;

            /** @var Talent|null $talent */
            $talent = $model->talents->first();
            $talentIdentifier = $talent ? new TalentIdentifier($talent->id) : null;

            $releaseDate = $model->release_date
                ? new ReleaseDate($model->release_date->toDateTimeImmutable())
                : null;

            $drafts[] = new DraftSong(
                new SongIdentifier($model->id),
                $model->published_id ? new SongIdentifier($model->published_id) : null,
                new TranslationSetIdentifier($model->translation_set_identifier),
                new Slug($model->slug),
                $model->editor_id ? new PrincipalIdentifier($model->editor_id) : null,
                Language::from($model->language),
                new SongName($model->name),
                $model->agency_id ? new AgencyIdentifier($model->agency_id) : null,
                $groupIdentifier,
                $talentIdentifier,
                new Lyricist($model->lyricist),
                new Composer($model->composer),
                $releaseDate,
                new Overview($model->overview),
                ApprovalStatus::from($model->status),
                $model->approver_id ? new PrincipalIdentifier($model->approver_id) : null,
                $model->merger_id ? new PrincipalIdentifier($model->merger_id) : null,
                null,
                $model->source_editor_id ? new PrincipalIdentifier($model->source_editor_id) : null,
                $model->translated_at?->toDateTimeImmutable(),
                $model->approved_at?->toDateTimeImmutable(),
            );
        }

        return $drafts;
    }
}
