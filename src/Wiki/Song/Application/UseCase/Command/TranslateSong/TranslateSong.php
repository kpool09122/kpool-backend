<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Application\UseCase\Command\TranslateSong;

use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Song\Application\Exception\SongNotFoundException;
use Source\Wiki\Song\Application\Service\TranslationServiceInterface;
use Source\Wiki\Song\Domain\Entity\DraftSong;
use Source\Wiki\Song\Domain\Repository\SongRepositoryInterface;

readonly class TranslateSong implements TranslateSongInterface
{
    public function __construct(
        private SongRepositoryInterface $songRepository,
        private TranslationServiceInterface $translationService,
        private PrincipalRepositoryInterface $principalRepository,
    ) {
    }

    /**
     * @param TranslateSongInputPort $input
     * @return DraftSong[]
     * @throws SongNotFoundException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function process(TranslateSongInputPort $input): array
    {
        $song = $this->songRepository->findById($input->songIdentifier());

        if ($song === null) {
            throw new SongNotFoundException();
        }

        $principal = $this->principalRepository->findById($input->principalIdentifier());
        if ($principal === null) {
            throw new PrincipalNotFoundException();
        }
        $resourceIdentifier = new ResourceIdentifier(
            type: ResourceType::SONG,
            agencyId: (string) $song->agencyIdentifier(),
            groupIds: [(string) $song->groupIdentifier()],
            talentIds: [(string) $song->talentIdentifier()],
        );

        if (! $principal->role()->can(Action::TRANSLATE, $resourceIdentifier, $principal)) {
            throw new UnauthorizedException();
        }

        $languages = Language::allExcept($song->language());

        $songDrafts = [];
        foreach ($languages as $language) {
            // 外部翻訳サービスを使って翻訳
            $songDraft = $this->translationService->translateSong($song, $language);
            $songDrafts[] = $songDraft;
            $this->songRepository->saveDraft($songDraft);
        }

        return $songDrafts;
    }
}
