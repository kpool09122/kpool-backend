<?php

declare(strict_types=1);

namespace Tests\Wiki\Agency\Infrastructure\Service;

use Application\Http\Client\GoogleTranslateClient\GoogleTranslateClient;
use Application\Http\Client\GoogleTranslateClient\TranslateTexts\TranslateTextsRequest;
use Application\Http\Client\GoogleTranslateClient\TranslateTexts\TranslateTextsResponse;
use DateTimeImmutable;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Agency\Domain\Entity\Agency;
use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Agency\Domain\ValueObject\Description;
use Source\Wiki\Agency\Infrastructure\Service\GoogleTranslationService;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Agency\CEO;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Agency\FoundedIn;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\Name;
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
    public function testTranslateAgencyReturnsTranslatedData(): void
    {
        $agency = $this->createDummyAgency();
        $targetLanguage = Language::ENGLISH;

        $expectedTranslations = [
            'JYP Entertainment',
            'J.Y. Park',
            '### JYP Entertainment',
        ];

        $googleTranslateClient = $this->createGoogleTranslateClientMock($expectedTranslations);

        $service = new GoogleTranslationService($googleTranslateClient);
        $result = $service->translateAgency($agency, $targetLanguage);

        $this->assertSame('JYP Entertainment', $result->translatedName());
        $this->assertSame('J.Y. Park', $result->translatedCEO());
        $this->assertSame('### JYP Entertainment', $result->translatedDescription());
    }

    /**
     * 正常系：GoogleTranslateClientに正しいパラメータが渡されること.
     */
    public function testTranslateAgencyCallsClientWithCorrectParameters(): void
    {
        $agency = $this->createDummyAgency();
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
                'JYPエンターテインメント',
                'J.Y. Park',
                '### JYPエンターテインメント',
            ]));

        $service = new GoogleTranslationService($googleTranslateClient);
        $service->translateAgency($agency, $targetLanguage);

        $this->assertNotNull($capturedRequest);
        $this->assertSame([
            (string) $agency->name(),
            (string) $agency->CEO(),
            (string) $agency->description(),
        ], $capturedRequest->texts());
        $this->assertSame($targetLanguage->value, $capturedRequest->targetLanguage());
    }

    /**
     * 異常系：翻訳結果が空の場合、元のAgencyの値がフォールバックとして使用されること.
     */
    public function testTranslateAgencyUsesOriginalValuesWhenTranslationIsEmpty(): void
    {
        $agency = $this->createDummyAgency();
        $targetLanguage = Language::ENGLISH;

        $googleTranslateClient = $this->createGoogleTranslateClientMock([]);

        $service = new GoogleTranslationService($googleTranslateClient);
        $result = $service->translateAgency($agency, $targetLanguage);

        $this->assertSame((string) $agency->name(), $result->translatedName());
        $this->assertSame((string) $agency->CEO(), $result->translatedCEO());
        $this->assertSame((string) $agency->description(), $result->translatedDescription());
    }

    /**
     * 異常系：翻訳結果の一部が欠けている場合、欠けている項目は元の値が使用されること.
     */
    public function testTranslateAgencyUsesOriginalValuesForMissingTranslations(): void
    {
        $agency = $this->createDummyAgency();
        $targetLanguage = Language::ENGLISH;

        // Only name is translated, CEO and description are missing
        $googleTranslateClient = $this->createGoogleTranslateClientMock(['JYP Entertainment']);

        $service = new GoogleTranslationService($googleTranslateClient);
        $result = $service->translateAgency($agency, $targetLanguage);

        $this->assertSame('JYP Entertainment', $result->translatedName());
        $this->assertSame((string) $agency->CEO(), $result->translatedCEO());
        $this->assertSame((string) $agency->description(), $result->translatedDescription());
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

    private function createDummyAgency(): Agency
    {
        return new Agency(
            new AgencyIdentifier(StrTestHelper::generateUuid()),
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            new Slug('jyp-entertainment'),
            Language::KOREAN,
            new Name('JYP엔터테인먼트'),
            'ㅈㅇㅍㅇㅌㅌㅇㅁㅌ',
            new CEO('박진영'),
            '박진영',
            new FoundedIn(new DateTimeImmutable('1997-04-25')),
            new Description('### JYP엔터테인먼트 (JYP Entertainment)'),
            new Version(1),
        );
    }
}
