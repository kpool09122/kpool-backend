<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Application\UseCase\Command\TranslateSong;

use Source\Shared\Domain\ValueObject\Translation;
use Source\Wiki\Song\Application\Exception\SongNotFoundException;
use Source\Wiki\Song\Application\Service\TranslationServiceInterface;
use Source\Wiki\Song\Domain\Entity\DraftSong;
use Source\Wiki\Song\Domain\Repository\SongRepositoryInterface;

class TranslateSong implements TranslateSongInterface
{
    public function __construct(
        private SongRepositoryInterface $songRepository,
        private TranslationServiceInterface $translationService,
    ) {
    }

    /**
     * @param TranslateSongInputPort $input
     * @return DraftSong[]
     * @throws SongNotFoundException
     */
    public function process(TranslateSongInputPort $input): array
    {
        $song = $this->songRepository->findById($input->songIdentifier());

        if ($song === null) {
            throw new SongNotFoundException();
        }

        $translations = Translation::allExcept($song->translation());

        $songDrafts = [];
        foreach ($translations as $translation) {
            // 外部翻訳サービスを使って翻訳
            $songDraft = $this->translationService->translateSong($song, $translation);
            $songDrafts[] = $songDraft;
            $this->songRepository->saveDraft($songDraft);
        }

        return $songDrafts;
    }
}
