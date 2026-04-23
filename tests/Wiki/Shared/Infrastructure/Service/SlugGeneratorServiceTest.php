<?php

declare(strict_types=1);

namespace Tests\Wiki\Shared\Infrastructure\Service;

use Illuminate\Contracts\Container\BindingResolutionException;
use PHPUnit\Framework\Attributes\DataProvider;
use Source\Wiki\Shared\Domain\Service\SlugGeneratorServiceInterface;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Shared\Infrastructure\Service\SlugGeneratorService;
use Tests\TestCase;

class SlugGeneratorServiceTest extends TestCase
{
    /**
     * @throws BindingResolutionException
     */
    public function testConstruct(): void
    {
        $service = $this->app->make(SlugGeneratorServiceInterface::class);

        $this->assertInstanceOf(SlugGeneratorService::class, $service);
    }

    #[DataProvider('generateProvider')]
    public function testGenerate(string $input, ResourceType $resourceType, string $expected): void
    {
        $service = $this->app->make(SlugGeneratorServiceInterface::class);

        $slug = $service->generate($input, $resourceType);

        $this->assertSame($expected, (string) $slug);
    }

    /**
     * @return array<string, array{string, ResourceType, string}>
     */
    public static function generateProvider(): array
    {
        return [
            'basic group' => ['hello-world', ResourceType::GROUP, 'gr-hello-world'],
            'lowercase conversion' => ['Hello-World', ResourceType::GROUP, 'gr-hello-world'],
            'spaces' => ['hello world', ResourceType::GROUP, 'gr-hello-world'],
            'special characters' => ['hello!@#$%world', ResourceType::GROUP, 'gr-hello-world'],
            'consecutive hyphens' => ['hello---world', ResourceType::GROUP, 'gr-hello-world'],
            'trim hyphens' => ['---hello-world---', ResourceType::GROUP, 'gr-hello-world'],
            'non ascii' => ['hello日本語world', ResourceType::GROUP, 'gr-hello-world'],
            'talent prefix' => ['Chaeyoung', ResourceType::TALENT, 'tl-chaeyoung'],
            'agency prefix' => ['JYP Entertainment', ResourceType::AGENCY, 'ag-jyp-entertainment'],
            'song prefix' => ['Signal', ResourceType::SONG, 'sg-signal'],
        ];
    }

    public function testGenerateEmptyStringReturnsPrefixedRandomSlug(): void
    {
        $service = $this->app->make(SlugGeneratorServiceInterface::class);

        $slug = $service->generate('', ResourceType::GROUP);

        $this->assertSame(13, mb_strlen((string) $slug));
        $this->assertMatchesRegularExpression('/^gr-[a-z0-9]{10}$/', (string) $slug);
    }

    public function testGenerateShortStringReturnsPrefixedRandomSlug(): void
    {
        $service = $this->app->make(SlugGeneratorServiceInterface::class);

        $slug = $service->generate('!', ResourceType::GROUP);

        $this->assertSame(13, mb_strlen((string) $slug));
        $this->assertMatchesRegularExpression('/^gr-[a-z0-9]{10}$/', (string) $slug);
    }

    public function testGenerateTruncatesLongString(): void
    {
        $service = $this->app->make(SlugGeneratorServiceInterface::class);

        $slug = $service->generate(str_repeat('a', 100), ResourceType::GROUP);

        $this->assertLessThanOrEqual(Slug::MAX_LENGTH, mb_strlen((string) $slug));
        $this->assertStringStartsWith('gr-', (string) $slug);
    }

    public function testGenerateTruncatesAndTrimsTrailingHyphen(): void
    {
        $service = $this->app->make(SlugGeneratorServiceInterface::class);

        $slug = $service->generate(str_repeat('a', 79) . '-b', ResourceType::GROUP);

        $this->assertStringEndsNotWith('-', (string) $slug);
    }

    public function testGenerateRandomSlugIsDifferentEachTime(): void
    {
        $service = $this->app->make(SlugGeneratorServiceInterface::class);

        $slugs = [];
        for ($i = 0; $i < 10; $i++) {
            $slugs[] = (string) $service->generate('', ResourceType::GROUP);
        }

        $this->assertGreaterThan(1, count(array_unique($slugs)));
    }
}
