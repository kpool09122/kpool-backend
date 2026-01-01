<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Domain\Service;

use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Song\Domain\Repository\SongRepositoryInterface;
use Source\Wiki\Song\Domain\ValueObject\SongIdentifier;

readonly class SongService implements SongServiceInterface
{
    public function __construct(
        private SongRepositoryInterface $songRepository,
    ) {
    }

    public function existsApprovedButNotTranslatedSong(
        TranslationSetIdentifier $translationSetIdentifier,
        SongIdentifier $excludeSongIdentifier,
    ): bool {
        $draftSongs = $this->songRepository->findDraftsByTranslationSet(
            $translationSetIdentifier,
        );

        foreach ($draftSongs as $draftSong) {
            // 自分自身は除外
            if ((string) $draftSong->songIdentifier() === (string) $excludeSongIdentifier) {
                continue;
            }

            // Approved状態のものが存在すればtrue
            if ($draftSong->status() === ApprovalStatus::Approved) {
                return true;
            }
        }

        return false;
    }
}
