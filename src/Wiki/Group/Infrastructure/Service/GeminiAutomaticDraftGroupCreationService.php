<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Infrastructure\Service;

use Application\Http\Client\GeminiClient\Exceptions\GeminiException;
use Application\Http\Client\GeminiClient\GeminiClient;
use Application\Http\Client\GeminiClient\GenerateGroup\GenerateGroupParams;
use Application\Http\Client\GeminiClient\GenerateGroup\GenerateGroupRequest;
use Psr\Log\LoggerInterface;
use Source\Wiki\Group\Application\UseCase\Command\AutomaticCreateDraftGroup\GeneratedGroupData;
use Source\Wiki\Group\Domain\Service\AutomaticDraftGroupCreationServiceInterface;
use Source\Wiki\Group\Domain\ValueObject\AutomaticDraftGroupCreationPayload;

readonly class GeminiAutomaticDraftGroupCreationService implements AutomaticDraftGroupCreationServiceInterface
{
    public function __construct(
        private GeminiClient $geminiClient,
        private LoggerInterface $logger,
    ) {
    }

    public function generate(
        AutomaticDraftGroupCreationPayload $payload,
    ): GeneratedGroupData {
        $request = new GenerateGroupRequest(
            groupName: (string)$payload->name(),
            language: $payload->language()->value,
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
