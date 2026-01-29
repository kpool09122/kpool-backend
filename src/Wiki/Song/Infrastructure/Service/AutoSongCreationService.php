<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Infrastructure\Service;

use Application\Http\Client\GeminiClient\Exceptions\GeminiException;
use Application\Http\Client\GeminiClient\GeminiClient;
use Application\Http\Client\GeminiClient\GenerateSong\GenerateSongParams;
use Application\Http\Client\GeminiClient\GenerateSong\GenerateSongRequest;
use Psr\Log\LoggerInterface;
use Source\Wiki\Song\Application\UseCase\Command\AutoCreateSong\GeneratedSongData;
use Source\Wiki\Song\Domain\Service\AutoSongCreationServiceInterface;
use Source\Wiki\Song\Domain\ValueObject\AutoSongCreationPayload;

readonly class AutoSongCreationService implements AutoSongCreationServiceInterface
{
    public function __construct(
        private GeminiClient $geminiClient,
        private LoggerInterface $logger,
    ) {
    }

    public function generate(
        AutoSongCreationPayload $payload,
    ): GeneratedSongData {
        $request = new GenerateSongRequest(
            songName: (string)$payload->name(),
            language: $payload->language()->value,
        );

        try {
            $response = $this->geminiClient->generateSong($request);
            $params = $response->params();
        } catch (GeminiException $e) {
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
