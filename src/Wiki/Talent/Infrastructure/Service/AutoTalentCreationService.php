<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Infrastructure\Service;

use Application\Http\Client\GeminiClient\GeminiClient;
use Application\Http\Client\GeminiClient\GenerateTalent\GenerateTalentParams;
use Application\Http\Client\GeminiClient\GenerateTalent\GenerateTalentRequest;
use Psr\Log\LoggerInterface;
use Source\Wiki\Agency\Domain\Repository\AgencyRepositoryInterface;
use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier as AgencyDomainIdentifier;
use Source\Wiki\Group\Domain\Repository\GroupRepositoryInterface;
use Source\Wiki\Shared\Domain\ValueObject\GroupIdentifier as GroupDomainIdentifier;
use Source\Wiki\Talent\Application\UseCase\Command\AutoCreateTalent\GeneratedTalentData;
use Source\Wiki\Talent\Domain\Service\AutoTalentCreationServiceInterface;
use Source\Wiki\Talent\Domain\ValueObject\AutoTalentCreationPayload;
use Throwable;

readonly class AutoTalentCreationService implements AutoTalentCreationServiceInterface
{
    public function __construct(
        private GeminiClient $geminiClient,
        private LoggerInterface $logger,
        private AgencyRepositoryInterface $agencyRepository,
        private GroupRepositoryInterface $groupRepository,
    ) {
    }

    public function generate(
        AutoTalentCreationPayload $payload,
    ): GeneratedTalentData {
        $agencyName = null;
        if ($payload->agencyIdentifier() !== null) {
            $agencyIdentifier = new AgencyDomainIdentifier((string)$payload->agencyIdentifier());
            $agency = $this->agencyRepository->findById($agencyIdentifier);
            if ($agency !== null) {
                $agencyName = (string)$agency->name();
            }
        }

        $groupNames = [];
        $groupIdentifiers = array_map(
            static fn ($id) => new GroupDomainIdentifier((string)$id),
            $payload->groupIdentifiers()
        );
        $groups = $this->groupRepository->findByIds($groupIdentifiers);
        foreach ($groups as $group) {
            $groupNames[] = (string)$group->name();
        }

        $request = new GenerateTalentRequest(
            talentName: (string)$payload->name(),
            language: $payload->language()->value,
            agencyName: $agencyName,
            groupNames: $groupNames,
        );

        try {
            $response = $this->geminiClient->generateTalent($request);
            $params = $response->params();
        } catch (Throwable $e) {
            $this->logger->error('Gemini API failed', [
                'message' => $e->getMessage(),
            ]);
            $params = GenerateTalentParams::empty();
        }

        return new GeneratedTalentData(
            alphabetName: $params->alphabetName(),
            realName: $params->realName(),
            birthday: $params->birthday(),
            description: $params->description(),
            sources: $params->sources(),
        );
    }
}
