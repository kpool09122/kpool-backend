<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Domain\ValueObject;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Wiki\Domain\ValueObject\WikiFontStyle;
use ValueError;

class WikiFontStyleTest extends TestCase
{
    #[DataProvider('validFontStyleProvider')]
    public function testFromWithValidFontStyle(string $fontStyle): void
    {
        $valueObject = WikiFontStyle::from($fontStyle);

        $this->assertSame($fontStyle, $valueObject->value);
    }

    public function testOptionsExposeStableMetadataForFrontend(): void
    {
        $options = WikiFontStyle::options();

        $this->assertCount(15, $options);
        $this->assertSame([
            'id' => 'ja_pop',
            'language' => 'ja',
            'category' => 'pop',
            'label' => 'ポップ',
        ], $options[0]);
        $this->assertSame(WikiFontStyle::values(), array_column($options, 'id'));
    }

    public function testThrowsValueErrorWithEmptyFontStyle(): void
    {
        $this->expectException(ValueError::class);

        WikiFontStyle::from('');
    }

    #[DataProvider('invalidFontStyleProvider')]
    public function testThrowsValueErrorWithInvalidFontStyle(string $fontStyle): void
    {
        $this->expectException(ValueError::class);

        WikiFontStyle::from($fontStyle);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function validFontStyleProvider(): array
    {
        return array_reduce(
            WikiFontStyle::values(),
            static function (array $cases, string $fontStyle): array {
                $cases[$fontStyle] = [$fontStyle];

                return $cases;
            },
            [],
        );
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidFontStyleProvider(): array
    {
        return [
            'unknown slug' => ['ja_unknown'],
            'actual font family' => ['Noto Sans JP'],
            'uppercase slug' => ['JA_POP'],
            'wrong language' => ['fr_serif'],
        ];
    }
}
