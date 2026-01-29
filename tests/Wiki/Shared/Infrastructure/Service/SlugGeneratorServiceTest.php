<?php

declare(strict_types=1);

namespace Tests\Wiki\Shared\Infrastructure\Service;

use Illuminate\Contracts\Container\BindingResolutionException;
use PHPUnit\Framework\Attributes\DataProvider;
use Source\Wiki\Shared\Domain\Service\SlugGeneratorServiceInterface;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Shared\Infrastructure\Service\SlugGeneratorService;
use Tests\TestCase;

class SlugGeneratorServiceTest extends TestCase
{
    /**
     * 正しくDIが動作していること.
     *
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $service = $this->app->make(SlugGeneratorServiceInterface::class);

        $this->assertInstanceOf(SlugGeneratorService::class, $service);
    }

    /**
     * 正常系: 基本的な文字列からSlugが生成されること.
     *
     * @throws BindingResolutionException
     */
    public function testGenerateBasicSlug(): void
    {
        $service = $this->app->make(SlugGeneratorServiceInterface::class);

        $slug = $service->generate('hello-world');

        $this->assertSame('hello-world', (string) $slug);
    }

    /**
     * 正常系: 大文字が小文字に変換されること.
     *
     * @throws BindingResolutionException
     */
    public function testGenerateConvertsToLowercase(): void
    {
        $service = $this->app->make(SlugGeneratorServiceInterface::class);

        $slug = $service->generate('Hello-World');

        $this->assertSame('hello-world', (string) $slug);
    }

    /**
     * 正常系: 空白がハイフンに変換されること.
     *
     * @throws BindingResolutionException
     */
    public function testGenerateConvertsSpacesToHyphens(): void
    {
        $service = $this->app->make(SlugGeneratorServiceInterface::class);

        $slug = $service->generate('hello world');

        $this->assertSame('hello-world', (string) $slug);
    }

    /**
     * 正常系: 特殊文字がハイフンに変換されること.
     *
     * @throws BindingResolutionException
     */
    public function testGenerateConvertsSpecialCharactersToHyphens(): void
    {
        $service = $this->app->make(SlugGeneratorServiceInterface::class);

        $slug = $service->generate('hello!@#$%world');

        $this->assertSame('hello-world', (string) $slug);
    }

    /**
     * 正常系: 連続するハイフンが1つに統合されること.
     *
     * @throws BindingResolutionException
     */
    public function testGenerateMergesConsecutiveHyphens(): void
    {
        $service = $this->app->make(SlugGeneratorServiceInterface::class);

        $slug = $service->generate('hello---world');

        $this->assertSame('hello-world', (string) $slug);
    }

    /**
     * 正常系: 先頭と末尾のハイフンが削除されること.
     *
     * @throws BindingResolutionException
     */
    public function testGenerateTrimsHyphens(): void
    {
        $service = $this->app->make(SlugGeneratorServiceInterface::class);

        $slug = $service->generate('---hello-world---');

        $this->assertSame('hello-world', (string) $slug);
    }

    /**
     * 正常系: 空文字列の場合、ランダムな10文字の英数字が生成されること.
     *
     * @throws BindingResolutionException
     */
    public function testGenerateEmptyStringReturnsRandomSlug(): void
    {
        $service = $this->app->make(SlugGeneratorServiceInterface::class);

        $slug = $service->generate('');

        $this->assertSame(10, mb_strlen((string) $slug));
        $this->assertMatchesRegularExpression('/^[a-z0-9]{10}$/', (string) $slug);
    }

    /**
     * 正常系: 最小文字数未満の場合、ランダムな10文字の英数字が生成されること.
     *
     * @throws BindingResolutionException
     */
    public function testGenerateShortStringReturnsRandomSlug(): void
    {
        $service = $this->app->make(SlugGeneratorServiceInterface::class);

        $slug = $service->generate('ab');

        $this->assertSame(10, mb_strlen((string) $slug));
        $this->assertMatchesRegularExpression('/^[a-z0-9]{10}$/', (string) $slug);
    }

    /**
     * 正常系: 記号のみの文字列の場合、ランダムな10文字の英数字が生成されること.
     *
     * @throws BindingResolutionException
     */
    public function testGenerateSymbolOnlyStringReturnsRandomSlug(): void
    {
        $service = $this->app->make(SlugGeneratorServiceInterface::class);

        $slug = $service->generate('!@#$%^&*()');

        $this->assertSame(10, mb_strlen((string) $slug));
        $this->assertMatchesRegularExpression('/^[a-z0-9]{10}$/', (string) $slug);
    }

    /**
     * 正常系: 80文字を超える場合、切り詰められること.
     *
     * @throws BindingResolutionException
     */
    public function testGenerateTruncatesLongString(): void
    {
        $service = $this->app->make(SlugGeneratorServiceInterface::class);

        $longText = str_repeat('a', 100);
        $slug = $service->generate($longText);

        $this->assertLessThanOrEqual(Slug::MAX_LENGTH, mb_strlen((string) $slug));
    }

    /**
     * 正常系: 切り詰め後に末尾のハイフンが削除されること.
     *
     * @throws BindingResolutionException
     */
    public function testGenerateTruncatesAndTrimsTrailingHyphen(): void
    {
        $service = $this->app->make(SlugGeneratorServiceInterface::class);

        // 80文字目がハイフンになるような文字列を生成
        $text = str_repeat('a', 79) . '-b';
        $slug = $service->generate($text);

        $this->assertStringEndsNotWith('-', (string) $slug);
    }

    /**
     * 正常系: 日本語などの非ASCII文字がハイフンに変換されること.
     *
     * @throws BindingResolutionException
     */
    public function testGenerateConvertsNonAsciiToHyphens(): void
    {
        $service = $this->app->make(SlugGeneratorServiceInterface::class);

        $slug = $service->generate('hello日本語world');

        $this->assertSame('hello-world', (string) $slug);
    }

    /**
     * 正常系: 複合的な変換が正しく行われること.
     *
     * @return array<string, array{string, string}>
     */
    public static function complexSlugProvider(): array
    {
        return [
            '大文字と空白' => ['Hello World Test', 'hello-world-test'],
            '数字を含む' => ['Article 123', 'article-123'],
            '複数の特殊文字' => ['test!@#test', 'test-test'],
            '日本語と英語の混在' => ['Test テスト Example', 'test-example'],
            'アンダースコア' => ['hello_world', 'hello-world'],
        ];
    }

    /**
     * 正常系: 複合的な変換が正しく行われること.
     */
    #[DataProvider('complexSlugProvider')]
    public function testGenerateComplexSlug(string $input, string $expected): void
    {
        $service = $this->app->make(SlugGeneratorServiceInterface::class);

        $slug = $service->generate($input);

        $this->assertSame($expected, (string) $slug);
    }

    /**
     * 正常系: ランダム生成されるSlugが毎回異なること.
     */
    public function testGenerateRandomSlugIsDifferentEachTime(): void
    {
        $service = $this->app->make(SlugGeneratorServiceInterface::class);

        $slugs = [];
        for ($i = 0; $i < 10; $i++) {
            $slugs[] = (string) $service->generate('');
        }

        $uniqueSlugs = array_unique($slugs);
        $this->assertGreaterThan(1, count($uniqueSlugs));
    }
}
