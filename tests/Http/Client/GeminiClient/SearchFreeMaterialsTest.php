<?php

declare(strict_types=1);

namespace Tests\Http\Client\GeminiClient;

use Application\Http\Client\Foundation\PsrFactories;
use Application\Http\Client\GeminiClient\GeminiClient;
use Application\Http\Client\GeminiClient\SearchFreeMaterials\SearchFreeMaterialsRequest;
use Mockery;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Tests\TestCase;

class SearchFreeMaterialsTest extends TestCase
{
    public function testSearchUsesGoogleSearchAndEditedContextInUserLanguage(): void
    {
        $factory = new Psr17Factory();
        /** @var ClientInterface&Mockery\MockInterface $httpClient */
        $httpClient = Mockery::mock(ClientInterface::class);
        $httpClient->shouldReceive('sendRequest')->once()->withArgs(function (RequestInterface $request): bool {
            /** @var array{tools: array<mixed>, contents: list<array{parts: list<array{text: string}>}>} $body */
            $body = json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);
            self::assertSame([['google_search' => []]], $body['tools']);
            $prompt = $body['contents'][0]['parts'][0]['text'];
            foreach (['Edited title', 'talent', 'English', 'New group', 'member_of', 'relatedWikis'] as $text) {
                self::assertStringContainsString($text, $prompt);
            }
            self::assertArrayNotHasKey('generationConfig', $body);

            return true;
        })->andReturn($factory->createResponse()->withBody($factory->createStream(json_encode([
            'candidates' => [['finishReason' => 'STOP', 'content' => ['parts' => [['text' => '{"sites":[]}']]]]],
        ], JSON_THROW_ON_ERROR))));
        $client = new GeminiClient('test-key', 'gemini-test', $httpClient, new PsrFactories($factory, $factory, $factory));
        $response = $client->searchFreeMaterials(new SearchFreeMaterialsRequest('Edited title', 'talent', 'en', [
            ['identifier' => 'test-id', 'title' => 'New group', 'resourceType' => 'group', 'relationType' => 'member_of'],
        ]));
        self::assertSame([], $response->sites());
    }
}
