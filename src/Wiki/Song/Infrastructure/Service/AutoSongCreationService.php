<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Infrastructure\Service;

use Application\Http\Client\GeminiClient\GeminiClient;
use Application\Http\Client\GeminiClient\GenerateSong\GenerateSongParams;
use Application\Http\Client\GeminiClient\GenerateSong\GenerateSongRequest;
use Psr\Log\LoggerInterface;
use Source\Wiki\Agency\Domain\Repository\AgencyRepositoryInterface;
use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier as AgencyDomainIdentifier;
use Source\Wiki\Group\Domain\Repository\GroupRepositoryInterface;
use Source\Wiki\Shared\Domain\ValueObject\GroupIdentifier as GroupDomainIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\TalentIdentifier as TalentDomainIdentifier;
use Source\Wiki\Song\Application\UseCase\Command\AutoCreateSong\GeneratedSongData;
use Source\Wiki\Song\Domain\Service\AutoSongCreationServiceInterface;
use Source\Wiki\Song\Domain\ValueObject\AutoSongCreationPayload;
use Source\Wiki\Talent\Domain\Repository\TalentRepositoryInterface;
use Throwable;

readonly class AutoSongCreationService implements AutoSongCreationServiceInterface
{
    public function __construct(
        private GeminiClient $geminiClient,
        private LoggerInterface $logger,
        private AgencyRepositoryInterface $agencyRepository,
        private GroupRepositoryInterface $groupRepository,
        private TalentRepositoryInterface $talentRepository,
    ) {
    }

    public function generate(
        AutoSongCreationPayload $payload,
    ): GeneratedSongData {
        $agencyName = null;
        if ($payload->agencyIdentifier() !== null) {
            $agencyIdentifier = new AgencyDomainIdentifier((string)$payload->agencyIdentifier());
            $agency = $this->agencyRepository->findById($agencyIdentifier);
            if ($agency !== null) {
                $agencyName = (string)$agency->name();
            }
        }

        $groupName = null;
        if ($payload->groupIdentifier() !== null) {
            $groupIdentifier = new GroupDomainIdentifier((string)$payload->groupIdentifier());
            $group = $this->groupRepository->findById($groupIdentifier);
            if ($group !== null) {
                $groupName = (string)$group->name();
            }
        }

        $talentName = null;
        if ($payload->talentIdentifier() !== null) {
            $talentIdentifier = new TalentDomainIdentifier((string)$payload->talentIdentifier());
            $talent = $this->talentRepository->findById($talentIdentifier);
            if ($talent !== null) {
                $talentName = (string)$talent->name();
            }
        }

        $request = new GenerateSongRequest(
            songName: (string)$payload->name(),
            language: $payload->language()->value,
            agencyName: $agencyName,
            groupName: $groupName,
            talentName: $talentName,
        );

        try {
            $response = $this->geminiClient->generateSong($request);
            $params = $response->params();
        } catch (Throwable $e) {
            $this->logger->error('Gemini API failed', [
                'message' => $e->getMessage(),
            ]);
            $params = GenerateSongParams::empty();
        }

        return new GeneratedSongData(
            alphabetName: $params->alphabetName(),
            lyricist: $params->lyricist(),
            composer: $params->composer(),
            releaseDate: $params->releaseDate(),
            overview: $params->overview(),
            sources: $params->sources(),
        );
    }
}
