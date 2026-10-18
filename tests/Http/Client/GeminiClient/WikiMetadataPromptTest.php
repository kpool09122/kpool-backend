<?php

declare(strict_types=1);

namespace Tests\Http\Client\GeminiClient;

use Application\Http\Client\GeminiClient\GenerateAgency\GenerateAgencyRequest;
use Application\Http\Client\GeminiClient\GenerateGroup\GenerateGroupRequest;
use Application\Http\Client\GeminiClient\GenerateSong\GenerateSongRequest;
use Application\Http\Client\GeminiClient\GenerateTalent\GenerateTalentRequest;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class WikiMetadataPromptTest extends TestCase
{
    /** @return array<string, array{GenerateAgencyRequest|GenerateGroupRequest|GenerateTalentRequest|GenerateSongRequest}> */
    public static function requests(): array
    {
        return [
            'agency' => [new GenerateAgencyRequest('JYP', 'ja')],
            'group' => [new GenerateGroupRequest('TWICE', 'ja')],
            'talent' => [new GenerateTalentRequest('NAYEON', 'ja')],
            'song' => [new GenerateSongRequest('TT', 'ja')],
        ];
    }

    #[DataProvider('requests')]
    public function testRequestsMetadataInTheOutputLanguage(GenerateAgencyRequest|GenerateGroupRequest|GenerateTalentRequest|GenerateSongRequest $request): void
    {
        $factory = new Psr17Factory();
        $psrRequest = $request->toPsrRequest($factory->createRequest('POST', 'https://example.com'), $factory);
        $body = json_decode((string) $psrRequest->getBody(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertIsArray($body);
        $this->assertIsArray($body['contents']);
        $this->assertIsArray($body['contents'][0]);
        $this->assertIsArray($body['contents'][0]['parts']);
        $this->assertIsArray($body['contents'][0]['parts'][0]);
        $prompt = $body['contents'][0]['parts'][0]['text'];
        $this->assertIsString($prompt);

        $this->assertStringContainsString('Japanese', $prompt);
        $this->assertStringContainsString('in the requested output language', $prompt);
        $this->assertStringContainsString('title:', $prompt);
        $this->assertStringContainsString('at most 40 characters', $prompt);
        $this->assertStringContainsString('meta_description:', $prompt);
        $this->assertStringContainsString('at most 140 characters', $prompt);
        $this->assertStringContainsString('keywords: At most 5', $prompt);
        $this->assertStringContainsString('at most 20 characters', $prompt);
    }
}
