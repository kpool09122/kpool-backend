<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Infrastructure\Service;

use Application\Http\Client\GeminiClient\GeminiClient;
use Application\Http\Client\GeminiClient\GenerateAgency\GenerateAgencyRequest;
use Application\Http\Client\GeminiClient\GenerateAgency\GenerateAgencyResponse;
use Application\Http\Client\GeminiClient\GenerateGroup\GenerateGroupRequest;
use Application\Http\Client\GeminiClient\GenerateGroup\GenerateGroupResponse;
use Application\Http\Client\GeminiClient\GenerateSong\GenerateSongRequest;
use Application\Http\Client\GeminiClient\GenerateSong\GenerateSongResponse;
use Application\Http\Client\GeminiClient\GenerateTalent\GenerateTalentRequest;
use Application\Http\Client\GeminiClient\GenerateTalent\GenerateTalentResponse;
use InvalidArgumentException;
use Mockery;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\Attributes\DataProvider;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Wiki\Domain\Service\AutoWikiCreationServiceInterface;
use Source\Wiki\Wiki\Domain\ValueObject\AutoWikiCreationPayload;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\Name;
use Tests\TestCase;

class AutoWikiCreationNotesTest extends TestCase
{
    /** @return array<string, array{ResourceType}> */
    public static function resourceTypes(): array
    {
        return [
            'agency' => [ResourceType::AGENCY],
            'group' => [ResourceType::GROUP],
            'talent' => [ResourceType::TALENT],
            'song' => [ResourceType::SONG],
        ];
    }

    #[DataProvider('resourceTypes')]
    public function testPassesNotesToGenerationRequest(ResourceType $resourceType): void
    {
        $factory = new Psr17Factory();
        $response = $factory->createResponse()->withBody($factory->createStream(json_encode([
            'candidates' => [['content' => ['parts' => [['text' => '{}']]]]],
        ], JSON_THROW_ON_ERROR)));
        $generatedResponse = match ($resourceType) {
            ResourceType::AGENCY => new GenerateAgencyResponse($response),
            ResourceType::GROUP => new GenerateGroupResponse($response),
            ResourceType::TALENT => new GenerateTalentResponse($response),
            ResourceType::SONG => new GenerateSongResponse($response),
            default => throw new InvalidArgumentException('Unsupported Wiki resource type.'),
        };
        $geminiClient = Mockery::mock(GeminiClient::class);
        $geminiClient->shouldReceive('generate' . ucfirst($resourceType->value))->once()
            ->withArgs(static fn (GenerateAgencyRequest|GenerateGroupRequest|GenerateSongRequest|GenerateTalentRequest $request) => $request->notes() === '元グループのメンバー。現在はソロ活動。')
            ->andReturn($generatedResponse);
        $this->app()->instance(GeminiClient::class, $geminiClient);
        $service = $this->app()->make(AutoWikiCreationServiceInterface::class);

        $service->generate(new AutoWikiCreationPayload(
            Language::JAPANESE,
            $resourceType,
            new Name('TWICE'),
            new Slug($resourceType->slugPrefix() . '-notes-test'),
            null,
            [],
            [],
            notes: '元グループのメンバー。現在はソロ活動。',
        ));
    }
}
