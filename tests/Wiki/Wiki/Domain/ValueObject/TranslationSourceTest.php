<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Domain\ValueObject;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Wiki\Domain\ValueObject\TranslationSource;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Tests\Helper\StrTestHelper;

class TranslationSourceTest extends TestCase
{
    public function testPreservesTheSourceWikiAndVersion(): void
    {
        $wikiIdentifier = new WikiIdentifier(StrTestHelper::generateUuid());
        $version = new Version(2);
        $source = new TranslationSource($wikiIdentifier, $version);

        $this->assertSame($wikiIdentifier, $source->wikiIdentifier());
        $this->assertSame($version, $source->version());
    }

    /** @return array<string, array{bool, int, bool}> */
    public static function sources(): array
    {
        return [
            'same Wiki and version' => [true, 2, true],
            'same Wiki, older version' => [true, 1, false],
            'same Wiki, newer version' => [true, 3, false],
            'different Wiki, same version' => [false, 2, false],
        ];
    }

    #[DataProvider('sources')]
    public function testEqualityIncludesWikiIdentifierAndVersion(bool $sameWiki, int $version, bool $expected): void
    {
        $wikiId = StrTestHelper::generateUuid();
        $source = new TranslationSource(new WikiIdentifier($wikiId), new Version(2));
        $other = new TranslationSource(
            new WikiIdentifier($sameWiki ? $wikiId : StrTestHelper::generateUuid()),
            new Version($version),
        );

        $this->assertSame($expected, $source->equals($other));
        $this->assertSame($expected, $other->equals($source));
    }
}
