<?php

declare(strict_types=1);

namespace Tests\Wiki\Song\Infrastructure\Service;

use Application\Http\Client\GoogleTranslateClient\GoogleTranslateClient;
use Application\Http\Client\GoogleTranslateClient\TranslateTexts\TranslateTextsRequest;
use Application\Http\Client\GoogleTranslateClient\TranslateTexts\TranslateTextsResponse;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Song\Domain\Entity\Song;
use Source\Wiki\Song\Domain\ValueObject\Overview;
use Source\Wiki\Song\Domain\ValueObject\SongIdentifier;
use Source\Wiki\Song\Domain\ValueObject\SongName;
use Source\Wiki\Song\Infrastructure\Service\GoogleTranslationService;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Song\Composer;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Song\Lyricist;
use Tests\Helper\StrTestHelper;

class GoogleTranslationServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * 正常系：翻訳サービスが正しく翻訳データを返すこと.
     */
    public function testTranslateSongReturnsTranslatedData(): void
    {
        $song = $this->createDummySong();
        $targetLanguage = Language::ENGLISH;

        $expectedTranslations = [
            'Feel Special',
            'J.Y. Park',
            'J.Y. Park',
            '### Feel Special is a song by TWICE',
        ];

        $googleTranslateClient = $this->createGoogleTranslateClientMock($expectedTranslations);

        $service = new GoogleTranslationService($googleTranslateClient);
        $result = $service->translateSong($song, $targetLanguage);

        $this->assertSame('Feel Special', $result->translatedName());
        $this->assertSame('J.Y. Park', $result->translatedLyricist());
        $this->assertSame('J.Y. Park', $result->translatedComposer());
        $this->assertSame('### Feel Special is a song by TWICE', $result->translatedOverview());
    }

    /**
     * 正常系：GoogleTranslateClientに正しいパラメータが渡されること.
     */
    public function testTranslateSongCallsClientWithCorrectParameters(): void
    {
        $song = $this->createDummySong();
        $targetLanguage = Language::JAPANESE;

        $capturedRequest = null;

        /** @var MockInterface&GoogleTranslateClient $googleTranslateClient */
        $googleTranslateClient = Mockery::mock(GoogleTranslateClient::class);
        $googleTranslateClient->shouldReceive('translateTexts')
            ->once()
            ->withArgs(function (TranslateTextsRequest $request) use (&$capturedRequest): bool {
                $capturedRequest = $request;

                return true;
            })
            ->andReturn(new TranslateTextsResponse([
                'フィール・スペシャル',
                'パク・ジニョン',
                'パク・ジニョン',
                '### フィール・スペシャルはTWICEの曲です',
            ]));

        $service = new GoogleTranslationService($googleTranslateClient);
        $service->translateSong($song, $targetLanguage);

        $this->assertNotNull($capturedRequest);
        $this->assertSame([
            (string) $song->name(),
            (string) $song->lyricist(),
            (string) $song->composer(),
            (string) $song->overView(),
        ], $capturedRequest->texts());
        $this->assertSame($targetLanguage->value, $capturedRequest->targetLanguage());
    }

    /**
     * 異常系：翻訳結果が空の場合、元のSongの値がフォールバックとして使用されること.
     */
    public function testTranslateSongUsesOriginalValuesWhenTranslationIsEmpty(): void
    {
        $song = $this->createDummySong();
        $targetLanguage = Language::ENGLISH;

        $googleTranslateClient = $this->createGoogleTranslateClientMock([]);

        $service = new GoogleTranslationService($googleTranslateClient);
        $result = $service->translateSong($song, $targetLanguage);

        $this->assertSame((string) $song->name(), $result->translatedName());
        $this->assertSame((string) $song->lyricist(), $result->translatedLyricist());
        $this->assertSame((string) $song->composer(), $result->translatedComposer());
        $this->assertSame((string) $song->overView(), $result->translatedOverview());
    }

    /**
     * 異常系：翻訳結果の一部が欠けている場合、欠けている項目は元の値が使用されること.
     */
    public function testTranslateSongUsesOriginalValuesForMissingTranslations(): void
    {
        $song = $this->createDummySong();
        $targetLanguage = Language::ENGLISH;

        // Only name and lyricist are translated, composer and overview are missing
        $googleTranslateClient = $this->createGoogleTranslateClientMock(['Feel Special', 'J.Y. Park']);

        $service = new GoogleTranslationService($googleTranslateClient);
        $result = $service->translateSong($song, $targetLanguage);

        $this->assertSame('Feel Special', $result->translatedName());
        $this->assertSame('J.Y. Park', $result->translatedLyricist());
        $this->assertSame((string) $song->composer(), $result->translatedComposer());
        $this->assertSame((string) $song->overView(), $result->translatedOverview());
    }

    /**
     * @param string[] $translations
     * @return MockInterface&GoogleTranslateClient
     */
    private function createGoogleTranslateClientMock(array $translations): MockInterface
    {
        /** @var MockInterface&GoogleTranslateClient $mock */
        $mock = Mockery::mock(GoogleTranslateClient::class);
        $mock->shouldReceive('translateTexts')
            ->once()
            ->andReturn(new TranslateTextsResponse($translations));

        return $mock;
    }

    private function createDummySong(): Song
    {
        return new Song(
            new SongIdentifier(StrTestHelper::generateUuid()),
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            new Slug('feel-special'),
            Language::KOREAN,
            new SongName('필스페셜'),
            null,
            null,
            null,
            new Lyricist('박진영'),
            new Composer('박진영'),
            null,
            new Overview('### 필스페셜은 트와이스의 곡입니다'),
            new Version(1),
        );
    }
}
