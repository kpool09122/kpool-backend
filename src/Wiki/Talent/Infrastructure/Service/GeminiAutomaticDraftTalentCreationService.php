<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Infrastructure\Service;

use Application\Http\Client\GeminiClient\Exceptions\GeminiException;
use Application\Http\Client\GeminiClient\GeminiClient;
use Application\Http\Client\GeminiClient\GenerateTalent\GenerateTalentParams;
use Application\Http\Client\GeminiClient\GenerateTalent\GenerateTalentRequest;
use Psr\Log\LoggerInterface;
use Source\Wiki\Talent\Application\UseCase\Command\AutomaticCreateDraftTalent\GeneratedTalentData;
use Source\Wiki\Talent\Domain\Service\AutomaticDraftTalentCreationServiceInterface;
use Source\Wiki\Talent\Domain\ValueObject\AutomaticDraftTalentCreationPayload;

readonly class GeminiAutomaticDraftTalentCreationService implements AutomaticDraftTalentCreationServiceInterface
{
    public function __construct(
        private GeminiClient $geminiClient,
        private LoggerInterface $logger,
    ) {
    }

    public function generate(
        AutomaticDraftTalentCreationPayload $payload,
    ): GeneratedTalentData {
        $request = new GenerateTalentRequest(
            talentName: (string)$payload->name(),
            language: $payload->language()->value,
        );

        try {
            $response = $this->geminiClient->generateTalent($request);
            $params = $response->params();
        } catch (GeminiException $e) {
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
