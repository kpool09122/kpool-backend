<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Infrastructure\Service;

use Application\Http\Client\GeminiClient\Exceptions\GeminiException;
use Application\Http\Client\GeminiClient\GeminiClient;
use Application\Http\Client\GeminiClient\GenerateGroup\GenerateGroupParams;
use Application\Http\Client\GeminiClient\GenerateGroup\GenerateGroupRequest;
use Psr\Log\LoggerInterface;
use Source\Wiki\Agency\Domain\Repository\AgencyRepositoryInterface;
use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier as AgencyDomainIdentifier;
use Source\Wiki\Group\Application\UseCase\Command\AutoCreateGroup\GeneratedGroupData;
use Source\Wiki\Group\Domain\Service\AutoGroupCreationServiceInterface;
use Source\Wiki\Group\Domain\ValueObject\AutoGroupCreationPayload;

readonly class AutoGroupCreationService implements AutoGroupCreationServiceInterface
{
    public function __construct(
        private GeminiClient $geminiClient,
        private LoggerInterface $logger,
        private AgencyRepositoryInterface $agencyRepository,
    ) {
    }

    public function generate(
        AutoGroupCreationPayload $payload,
    ): GeneratedGroupData {
        $agencyName = null;
        if ($payload->agencyIdentifier() !== null) {
            $agencyIdentifier = new AgencyDomainIdentifier((string)$payload->agencyIdentifier());
            $agency = $this->agencyRepository->findById($agencyIdentifier);
            if ($agency !== null) {
                $agencyName = (string)$agency->name();
            }
        }

        $request = new GenerateGroupRequest(
            groupName: (string)$payload->name(),
            language: $payload->language()->value,
            agencyName: $agencyName,
        );

        try {
            $response = $this->geminiClient->generateGroup($request);
            $params = $response->params();
        } catch (GeminiException $e) {
            $this->logger->error('Gemini API failed', [
                'message' => $e->getMessage(),
            ]);
            $params = GenerateGroupParams::empty();
        }

        return new GeneratedGroupData(
            alphabetName: $params->alphabetName(),
            description: $params->description(),
            sources: $params->sources(),
        );
    }
}
