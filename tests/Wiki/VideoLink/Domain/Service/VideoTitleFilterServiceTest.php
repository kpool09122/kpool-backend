<?php

declare(strict_types=1);

namespace Tests\Wiki\VideoLink\Domain\Service;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Shared\Domain\Service\NormalizationServiceInterface;
use Source\Wiki\Shared\Infrastructure\Service\NormalizationService;
use Source\Wiki\VideoLink\Domain\Service\VideoTitleFilterService;

class VideoTitleFilterServiceTest extends TestCase
{
    /** @param array<string, list<string>> $names */
    #[DataProvider('titleCases')]
    public function testMatchesNormalizedTitleAndNames(array $names, string $title, bool $expected): void
    {
        $service = new VideoTitleFilterService(new NormalizationService());

        self::assertSame($expected ? ['video' => $title] : [], $service->filter(['video' => $title], $names));
    }

    public function testPreservesOriginalJapaneseMatchWhenReadingChangesInContext(): void
    {
        $normalizationService = $this->createStub(NormalizationServiceInterface::class);
        $normalizationService->method('normalize')->willReturnMap([
            ['生', Language::JAPANESE, 'なま'],
            ['生出演', Language::JAPANESE, 'せいしゅつえん'],
        ]);
        $service = new VideoTitleFilterService($normalizationService);

        self::assertSame(['video' => '生出演'], $service->filter(['video' => '生出演'], ['ja' => ['生']]));
    }

    public function testNormalizesNamesOnceForMultipleTitlesAndPreservesKeys(): void
    {
        $normalizationService = $this->createMock(NormalizationServiceInterface::class);
        $normalizationService->expects(self::exactly(3))->method('normalize')->willReturnMap([
            ['TWICE', Language::ENGLISH, 'twice'],
            ['twice live', Language::ENGLISH, 'twice live'],
            ['Other artists', Language::ENGLISH, 'other artists'],
        ]);
        $service = new VideoTitleFilterService($normalizationService);

        self::assertSame(['first' => 'twice live'], $service->filter(
            ['first' => 'twice live', 'second' => 'Other artists'],
            ['en' => ['TWICE']],
        ));
    }

    public function testDoesNotReuseNamesFromPreviousCall(): void
    {
        $service = new VideoTitleFilterService(new NormalizationService());
        $titles = ['first' => 'TWICE live', 'second' => 'MOMO live'];

        self::assertSame(['first' => 'TWICE live'], $service->filter($titles, ['en' => ['TWICE']]));
        self::assertSame(['second' => 'MOMO live'], $service->filter($titles, ['en' => ['MOMO']]));
    }

    /** @return array<string, array{array<string, list<string>>, string, bool}> */
    public static function titleCases(): array
    {
        return [
            'English case' => [['en' => ['TWICE']], 'twice Official Video', true],
            'Korean initial consonants on both sides' => [['ko' => ['트와이스']], 'ㅌㅇㅇㅅ 무대', true],
            'Japanese reading on both sides' => [['ja' => ['東京']], 'とうきょう LIVE', true],
            'Japanese kana' => [['ja' => ['トゥワイス']], 'とぅわいす ライブ', true],
            'any registered language' => [['ko' => ['트와이스'], 'en' => ['TWICE']], 'twice live', true],
            'substring is sufficient' => [['en' => ['IVE']], 'LIVE performance', true],
            'unrelated' => [['en' => ['TWICE']], 'Other group MV', false],
            'empty names' => [['en' => ['', '  ']], 'Anything', false],
            'no names' => [[], 'Anything', false],
            'empty title' => [['en' => ['TWICE']], '', false],
        ];
    }
}
