<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Application\UseCase\Command\TranslateSong;

use DateTimeImmutable;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\Resource;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Song\Application\Exception\SongNotFoundException;
use Source\Wiki\Song\Application\Service\TranslationServiceInterface;
use Source\Wiki\Song\Domain\Entity\DraftSong;
use Source\Wiki\Song\Domain\Factory\DraftSongFactoryInterface;
use Source\Wiki\Song\Domain\Repository\DraftSongRepositoryInterface;
use Source\Wiki\Song\Domain\Repository\SongRepositoryInterface;
use Source\Wiki\Song\Domain\ValueObject\Overview;
use Source\Wiki\Song\Domain\ValueObject\SongName;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Song\Composer;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Song\Lyricist;

readonly class TranslateSong implements TranslateSongInterface
{
    public function __construct(
        private SongRepositoryInterface      $songRepository,
        private DraftSongRepositoryInterface $draftSongRepository,
        private TranslationServiceInterface  $translationService,
        private DraftSongFactoryInterface    $draftSongFactory,
        private PrincipalRepositoryInterface $principalRepository,
        private PolicyEvaluatorInterface     $policyEvaluator,
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
        $resource = new Resource(
            type: ResourceType::SONG,
            agencyId: (string) $song->agencyIdentifier(),
            groupIds: [(string) $song->groupIdentifier()],
            talentIds: [(string) $song->talentIdentifier()],
        );

        if (! $this->policyEvaluator->evaluate($principal, Action::TRANSLATE, $resource)) {
            throw new UnauthorizedException();
        }

        $languages = Language::allExcept($song->language());

        $songDrafts = [];
        $translatedAt = new DateTimeImmutable();
        foreach ($languages as $language) {
            $translatedData = $this->translationService->translateSong($song, $language);

            $songDraft = $this->draftSongFactory->create(
                editorIdentifier: null,
                slug: $song->slug(),
                language: $language,
                name: new SongName($translatedData->translatedName()),
                translationSetIdentifier: $song->translationSetIdentifier(),
            );

            $songDraft->setLyricist(new Lyricist($translatedData->translatedLyricist()));
            $songDraft->setComposer(new Composer($translatedData->translatedComposer()));
            $songDraft->setOverView(new Overview($translatedData->translatedOverview()));
            if ($song->agencyIdentifier() !== null) {
                $songDraft->setAgencyIdentifier($song->agencyIdentifier());
            }
            if ($song->groupIdentifier() !== null) {
                $songDraft->setGroupIdentifier($song->groupIdentifier());
            }
            if ($song->talentIdentifier() !== null) {
                $songDraft->setTalentIdentifier($song->talentIdentifier());
            }
            if ($song->releaseDate() !== null) {
                $songDraft->setReleaseDate($song->releaseDate());
            }
            $songDraft->setPublishedSongIdentifier($input->publishedSongIdentifier() ?? $input->songIdentifier());
            $songDraft->setSourceEditorIdentifier($song->editorIdentifier());
            $songDraft->setTranslatedAt($translatedAt);

            $songDrafts[] = $songDraft;
            $this->draftSongRepository->save($songDraft);
        }

        return $songDrafts;
    }
}
