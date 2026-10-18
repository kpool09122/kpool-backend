<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Infrastructure\Service;

use Application\Http\Client\GeminiClient\GeminiClient;
use Application\Http\Client\GeminiClient\GenerateAgency\GenerateAgencyResponse;
use Application\Http\Client\GeminiClient\GenerateGroup\GenerateGroupResponse;
use Application\Http\Client\GeminiClient\GenerateSong\GenerateSongResponse;
use Application\Http\Client\GeminiClient\GenerateTalent\GenerateTalentResponse;
use InvalidArgumentException;
use Mockery;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\Attributes\DataProvider;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Wiki\Application\UseCase\Command\AutoCreateWiki\GeneratedWikiData;
use Source\Wiki\Wiki\Domain\Service\AutoWikiCreationServiceInterface;
use Source\Wiki\Wiki\Domain\ValueObject\AutoWikiCreationPayload;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\Name;
use Tests\TestCase;

class AutoWikiCreationMetadataTest extends TestCase
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
    public function testGeneratesMetadataForEveryResourceType(ResourceType $resourceType): void
    {
        $result = $this->generate($resourceType, [
            'title' => ' TWICEのプロフィール ',
            'meta_description' => ' TWICEの活動と歴史を紹介します。 ',
            'keywords' => [' TWICE ', 'K-POP'],
        ]);

        $this->assertSame('TWICEのプロフィール', (string) $result->title());
        $this->assertSame('TWICEの活動と歴史を紹介します。', (string) $result->metaDescription());
        $this->assertSame(['TWICE', 'K-POP'], $result->keywords()?->values());
    }

    public function testNormalizesOversizedAndMalformedMetadata(): void
    {
        $result = $this->generate(ResourceType::GROUP, [
            'title' => str_repeat('あ', 41),
            'meta_description' => str_repeat('い', 141),
            'keywords' => ['', ' ', null, 123, ['invalid'], str_repeat('う', 21), str_repeat('う', 20), 'a', 'b', 'c', 'd', 'e'],
        ]);

        $this->assertSame(str_repeat('あ', 40), (string) $result->title());
        $this->assertSame(str_repeat('い', 140), (string) $result->metaDescription());
        $this->assertSame([str_repeat('う', 20), 'a', 'b', 'c', 'd'], $result->keywords()?->values());
    }

    /** @return array<string, array{array<string, mixed>}> */
    public static function unavailableMetadata(): array
    {
        return [
            'missing' => [[]],
            'null' => [['title' => null, 'meta_description' => null, 'keywords' => null]],
            'invalid types' => [['title' => [], 'meta_description' => 123, 'keywords' => 'invalid']],
            'empty' => [['title' => ' ', 'meta_description' => ' ', 'keywords' => [' ']]],
        ];
    }

    /** @param array<string, mixed> $metadata */
    #[DataProvider('unavailableMetadata')]
    public function testUnavailableMetadataDoesNotFailGeneration(array $metadata): void
    {
        $result = $this->generate(ResourceType::GROUP, $metadata);

        $this->assertNull($result->title());
        $this->assertNull($result->metaDescription());
        $this->assertNull($result->keywords());
    }

    /** @param array<string, mixed> $data */
    private function generate(ResourceType $resourceType, array $data): GeneratedWikiData
    {
        $factory = new Psr17Factory();
        $response = $factory->createResponse()->withBody($factory->createStream(json_encode([
            'candidates' => [['content' => ['parts' => [['text' => json_encode($data, JSON_THROW_ON_ERROR)]]]]],
        ], JSON_THROW_ON_ERROR)));
        $generatedResponse = match ($resourceType) {
            ResourceType::AGENCY => new GenerateAgencyResponse($response),
            ResourceType::GROUP => new GenerateGroupResponse($response),
            ResourceType::TALENT => new GenerateTalentResponse($response),
            ResourceType::SONG => new GenerateSongResponse($response),
            default => throw new InvalidArgumentException('Unsupported Wiki resource type.'),
        };
        $geminiClient = Mockery::mock(GeminiClient::class);
        $geminiClient->shouldReceive('generate' . ucfirst($resourceType->value))->once()->andReturn($generatedResponse);
        $this->app()->instance(GeminiClient::class, $geminiClient);
        $service = $this->app()->make(AutoWikiCreationServiceInterface::class);

        return $service->generate(new AutoWikiCreationPayload(
            Language::JAPANESE,
            $resourceType,
            new Name('TWICE'),
            new Slug($resourceType->slugPrefix() . '-metadata-test'),
            null,
            [],
            [],
        ));
    }
}
