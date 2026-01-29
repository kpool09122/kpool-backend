<?php

declare(strict_types=1);

namespace Application\Http\Client\GeminiClient;

use Application\Http\Client\Foundation\PsrFactories;
use Application\Http\Client\GeminiClient\Exceptions\GeminiException;
use Application\Http\Client\GeminiClient\GenerateAgency\GenerateAgencyRequest;
use Application\Http\Client\GeminiClient\GenerateAgency\GenerateAgencyResponse;
use Application\Http\Client\GeminiClient\GenerateGroup\GenerateGroupRequest;
use Application\Http\Client\GeminiClient\GenerateGroup\GenerateGroupResponse;
use Application\Http\Client\GeminiClient\GenerateTalent\GenerateTalentRequest;
use Application\Http\Client\GeminiClient\GenerateTalent\GenerateTalentResponse;
use JsonException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class GeminiClient
{
    private const string API_BASE_URL = 'https://generativelanguage.googleapis.com/v1beta/models';

    public function __construct(
        private readonly string $apiKey,
        private readonly string $model,
        private readonly ClientInterface $httpClient,
        private readonly PsrFactories $psrFactories,
    ) {
    }

    public function isConfigured(): bool
    {
        return $this->apiKey !== '';
    }

    /**
     * @throws GeminiException
     * @throws JsonException
     */
    public function generateAgency(GenerateAgencyRequest $request): GenerateAgencyResponse
    {
        if (! $this->isConfigured()) {
            throw new GeminiException('Gemini API key is not configured');
        }

        $baseRequest = $this->createBaseRequest();
        $psrRequest = $request->toPsrRequest(
            $baseRequest,
            $this->psrFactories->getStreamFactory(),
        );

        $response = $this->sendRequest($psrRequest);

        return new GenerateAgencyResponse($response);
    }

    /**
     * @throws GeminiException
     * @throws JsonException
     */
    public function generateGroup(GenerateGroupRequest $request): GenerateGroupResponse
    {
        if (! $this->isConfigured()) {
            throw new GeminiException('Gemini API key is not configured');
        }

        $baseRequest = $this->createBaseRequest();
        $psrRequest = $request->toPsrRequest(
            $baseRequest,
            $this->psrFactories->getStreamFactory(),
        );

        $response = $this->sendRequest($psrRequest);

        return new GenerateGroupResponse($response);
    }

    /**
     * @throws GeminiException
     * @throws JsonException
     */
    public function generateTalent(GenerateTalentRequest $request): GenerateTalentResponse
    {
        if (! $this->isConfigured()) {
            throw new GeminiException('Gemini API key is not configured');
        }

        $baseRequest = $this->createBaseRequest();
        $psrRequest = $request->toPsrRequest(
            $baseRequest,
            $this->psrFactories->getStreamFactory(),
        );

        $response = $this->sendRequest($psrRequest);

        return new GenerateTalentResponse($response);
    }

    private function createBaseRequest(): RequestInterface
    {
        $url = self::API_BASE_URL . '/' . $this->model . ':generateContent?key=' . $this->apiKey;

        return $this->psrFactories->getRequestFactory()->createRequest('POST', $url);
    }

    /**
     * @throws GeminiException
     */
    private function sendRequest(RequestInterface $request): ResponseInterface
    {
        try {
            return $this->httpClient->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            throw new GeminiException(
                sprintf('Gemini API request failed: %s', $e->getMessage()),
                0,
                $e,
            );
        }
    }
}
