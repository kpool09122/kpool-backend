<?php

declare(strict_types=1);

namespace Tests\Http\Client\GeminiClient;

use Application\Http\Client\Foundation\PsrFactories;
use Application\Http\Client\GeminiClient\Exceptions\GeminiException;
use Application\Http\Client\GeminiClient\GeminiClient;
use Application\Http\Client\GeminiClient\GenerateTalent\GenerateTalentRequest;
use Mockery;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Tests\TestCase;

class GeminiClientTest extends TestCase
{
    public function testGenerateTalentRequestUsesSupportedGoogleSearchToolPayload(): void
    {
        $psr17Factory = new Psr17Factory();
        $response = $psr17Factory->createResponse()
            ->withBody($psr17Factory->createStream(json_encode([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode([
                                        'alphabet_name' => 'Giselle',
                                    ], JSON_THROW_ON_ERROR),
                                ],
                            ],
                        ],
                    ],
                ],
            ], JSON_THROW_ON_ERROR)));

        /** @var ClientInterface&Mockery\MockInterface $httpClient */
        $httpClient = Mockery::mock(ClientInterface::class);
        $httpClient->shouldReceive('sendRequest')
            ->once()
            ->withArgs(function (RequestInterface $request): bool {
                $body = json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);
                $googleSearch = $body['tools'][0]['google_search'] ?? null;

                return $googleSearch === []
                    && ! isset($body['generationConfig']['responseMimeType'])
                    && ! isset($body['generationConfig']['responseSchema']);
            })
            ->andReturn($response);

        $client = new GeminiClient(
            apiKey: 'test-api-key',
            model: 'gemini-test',
            httpClient: $httpClient,
            psrFactories: new PsrFactories($psr17Factory, $psr17Factory, $psr17Factory),
        );

        $result = $client->generateTalent(new GenerateTalentRequest(
            talentName: 'Giselle',
            language: 'ja',
        ));

        $this->assertSame('Giselle', $result->params()->alphabetName());
    }

    public function testGenerateTalentThrowsExceptionWhenApiReturnsHttpError(): void
    {
        $psr17Factory = new Psr17Factory();
        $response = $psr17Factory->createResponse(400, 'Bad Request')
            ->withBody($psr17Factory->createStream(json_encode([
                'error' => [
                    'message' => 'Invalid request payload',
                    'status' => 'INVALID_ARGUMENT',
                ],
            ], JSON_THROW_ON_ERROR)));

        /** @var ClientInterface&Mockery\MockInterface $httpClient */
        $httpClient = Mockery::mock(ClientInterface::class);
        $httpClient->shouldReceive('sendRequest')
            ->once()
            ->andReturn($response);

        $client = new GeminiClient(
            apiKey: 'test-api-key',
            model: 'gemini-test',
            httpClient: $httpClient,
            psrFactories: new PsrFactories($psr17Factory, $psr17Factory, $psr17Factory),
        );

        $this->expectException(GeminiException::class);
        $this->expectExceptionMessage('Gemini API returned HTTP 400 Bad Request: Invalid request payload [INVALID_ARGUMENT]');

        $client->generateTalent(new GenerateTalentRequest(
            talentName: 'Giselle',
            language: 'ja',
        ));
    }
}
