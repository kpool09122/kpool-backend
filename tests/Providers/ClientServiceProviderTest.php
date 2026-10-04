<?php

declare(strict_types=1);

namespace Tests\Providers;

use Application\Http\Client\GeminiClient\GeminiClient;
use Application\Http\Client\GeminiClient\GenerateTalent\GenerateTalentRequest;
use Mockery;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Tests\TestCase;

class ClientServiceProviderTest extends TestCase
{
    #[DataProvider('modelSettings')]
    public function testGeminiUsesConfiguredModelOrDefaultWithoutNullConstructorArguments(?string $model, string $expectedModel): void
    {
        config(['google' => ['gemini_api_key' => 'test-api-key']]);
        if ($model !== null) {
            config(['google.gemini_model' => $model]);
        }

        $factory = new Psr17Factory();
        $response = $factory->createResponse()->withBody($factory->createStream(json_encode([
            'candidates' => [['content' => ['parts' => [['text' => '{"alphabet_name":"Sample"}']]]]],
        ], JSON_THROW_ON_ERROR)));
        $httpClient = Mockery::mock(ClientInterface::class);
        $httpClient->shouldReceive('sendRequest')->once()
            ->withArgs(function (RequestInterface $request) use ($expectedModel): bool {
                self::assertSame('/v1beta/models/' . $expectedModel . ':generateContent', $request->getUri()->getPath());

                return true;
            })->andReturn($response);
        $this->app()->instance(ClientInterface::class, $httpClient);

        $client = $this->app()->make(GeminiClient::class);
        $result = $client->generateTalent(new GenerateTalentRequest(talentName: 'Sample', language: 'en'));

        self::assertSame('Sample', $result->params()->alphabetName());
    }

    /** @return array<string, array{?string, string}> */
    public static function modelSettings(): array
    {
        return [
            'missing config uses default' => [null, GeminiClient::DEFAULT_MODEL],
            'configured model is used' => ['gemini-test', 'gemini-test'],
        ];
    }

    public function testMissingGeminiConfigCanResolveUnconfiguredClient(): void
    {
        config(['google' => []]);

        self::assertFalse($this->app()->make(GeminiClient::class)->isConfigured());
    }
}
