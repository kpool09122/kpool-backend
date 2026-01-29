<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Infrastructure\Service;

use Application\Http\Client\GeminiClient\Exceptions\GeminiException;
use Application\Http\Client\GeminiClient\GeminiClient;
use Application\Http\Client\GeminiClient\GenerateAgency\GenerateAgencyParams;
use Application\Http\Client\GeminiClient\GenerateAgency\GenerateAgencyRequest;
use Psr\Log\LoggerInterface;
use Source\Wiki\Agency\Application\UseCase\Command\AutoCreateAgency\GeneratedAgencyData;
use Source\Wiki\Agency\Domain\Service\AutoAgencyCreationServiceInterface;
use Source\Wiki\Agency\Domain\ValueObject\AutoAgencyCreationPayload;

readonly class AutoAgencyCreationService implements AutoAgencyCreationServiceInterface
{
    public function __construct(
        private GeminiClient $geminiClient,
        private LoggerInterface $logger,
    ) {
    }

    public function generate(
        AutoAgencyCreationPayload $payload,
    ): GeneratedAgencyData {
        $request = new GenerateAgencyRequest(
            agencyName: (string)$payload->name(),
            language: $payload->language()->value,
        );

        try {
            $response = $this->geminiClient->generateAgency($request);
            $params = $response->params();
        } catch (GeminiException $e) {
            $this->logger->error('Gemini API failed', [
                'message' => $e->getMessage(),
            ]);
            $params = GenerateAgencyParams::empty();
        }

        return new GeneratedAgencyData(
            alphabetName: $params->alphabetName(),
            ceoName: $params->ceoName(),
            foundedYear: $params->foundedYear(),
            description: $params->description(),
            sources: $params->sources(),
        );
    }
}
