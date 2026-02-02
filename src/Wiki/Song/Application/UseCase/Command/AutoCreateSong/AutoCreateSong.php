<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Application\UseCase\Command\AutoCreateSong;

use DateTimeImmutable;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\Service\SlugGeneratorServiceInterface;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\Resource;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Song\Domain\Entity\DraftSong;
use Source\Wiki\Song\Domain\Factory\DraftSongFactoryInterface;
use Source\Wiki\Song\Domain\Repository\DraftSongRepositoryInterface;
use Source\Wiki\Song\Domain\Service\AutoSongCreationServiceInterface;
use Source\Wiki\Song\Domain\ValueObject\Overview;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Song\Composer;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Song\Lyricist;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Song\ReleaseDate;

readonly class AutoCreateSong implements AutoCreateSongInterface
{
    public function __construct(
        private AutoSongCreationServiceInterface $automaticDraftSongCreationService,
        private DraftSongFactoryInterface        $draftSongFactory,
        private DraftSongRepositoryInterface     $draftSongRepository,
        private PrincipalRepositoryInterface     $principalRepository,
        private PolicyEvaluatorInterface         $policyEvaluator,
        private SlugGeneratorServiceInterface    $slugGeneratorService,
    ) {
    }

    /**
     * @param AutoCreateSongInputPort $input
     * @return DraftSong
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    public function process(AutoCreateSongInputPort $input): DraftSong
    {
        $principal = $this->principalRepository->findById($input->principalIdentifier());
        if ($principal === null) {
            throw new PrincipalNotFoundException();
        }

        $resource = new Resource(
            type: ResourceType::SONG,
            agencyId: $principal->agencyId(),
            groupIds: $principal->groupIds(),
            talentIds: $principal->talentIds(),
        );

        if (! $this->policyEvaluator->evaluate($principal, Action::AUTOMATIC_CREATE, $resource)) {
            throw new DisallowedException();
        }

        $payload = $input->payload();
        $generatedData = $this->automaticDraftSongCreationService->generate($payload);

        $slugSource = $generatedData->alphabetName() ?? (string)$payload->name();
        $slug = $this->slugGeneratorService->generate($slugSource);

        $draftSong = $this->draftSongFactory->create(
            editorIdentifier: null,
            slug: $slug,
            language: $payload->language(),
            name: $payload->name(),
        );

        if ($payload->agencyIdentifier() !== null) {
            $draftSong->setAgencyIdentifier($payload->agencyIdentifier());
        }

        if ($payload->groupIdentifier() !== null) {
            $draftSong->setGroupIdentifier($payload->groupIdentifier());
        }

        if ($payload->talentIdentifier() !== null) {
            $draftSong->setTalentIdentifier($payload->talentIdentifier());
        }

        $lyricist = $generatedData->lyricist() ?? '';
        $draftSong->setLyricist(new Lyricist($lyricist));

        $composer = $generatedData->composer() ?? '';
        $draftSong->setComposer(new Composer($composer));

        if ($generatedData->releaseDate() !== null) {
            $draftSong->setReleaseDate(new ReleaseDate(new DateTimeImmutable($generatedData->releaseDate())));
        }

        $overview = $generatedData->overview() ?? '';
        $draftSong->setOverView(new Overview($overview));

        $this->draftSongRepository->save($draftSong);

        return $draftSong;
    }
}
