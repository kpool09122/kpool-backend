<?php

declare(strict_types=1);

namespace Tests\Wiki\Talent\Infrastructure\Service;

use Application\Http\Client\GoogleTranslateClient\GoogleTranslateClient;
use Application\Http\Client\GoogleTranslateClient\TranslateTexts\TranslateTextsRequest;
use Application\Http\Client\GoogleTranslateClient\TranslateTexts\TranslateTextsResponse;
use DateTimeImmutable;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Shared\Domain\ValueObject\TalentIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Talent\Domain\Entity\Talent;
use Source\Wiki\Talent\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\Career;
use Source\Wiki\Talent\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\TalentName;
use Source\Wiki\Talent\Infrastructure\Service\GoogleTranslationService;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Talent\Birthday;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Talent\RealName;
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
    public function testTranslateTalentReturnsTranslatedData(): void
    {
        $talent = $this->createDummyTalent();
        $targetLanguage = Language::ENGLISH;

        $expectedTranslations = [
            'Chaeyoung',
            'Son Chaeyoung',
            '### Chaeyoung is a member of TWICE.',
        ];

        $googleTranslateClient = $this->createGoogleTranslateClientMock($expectedTranslations);

        $service = new GoogleTranslationService($googleTranslateClient);
        $result = $service->translateTalent($talent, $targetLanguage);

        $this->assertSame('Chaeyoung', $result->translatedName());
        $this->assertSame('Son Chaeyoung', $result->translatedRealName());
        $this->assertSame('### Chaeyoung is a member of TWICE.', $result->translatedCareer());
    }

    /**
     * 正常系：GoogleTranslateClientに正しいパラメータが渡されること.
     */
    public function testTranslateTalentCallsClientWithCorrectParameters(): void
    {
        $talent = $this->createDummyTalent();
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
                'チェヨン',
                'ソン・チェヨン',
                '### チェヨンはTWICEのメンバーです。',
            ]));

        $service = new GoogleTranslationService($googleTranslateClient);
        $service->translateTalent($talent, $targetLanguage);

        $this->assertNotNull($capturedRequest);
        $this->assertSame([
            (string) $talent->name(),
            (string) $talent->realName(),
            (string) $talent->career(),
        ], $capturedRequest->texts());
        $this->assertSame($targetLanguage->value, $capturedRequest->targetLanguage());
    }

    /**
     * 異常系：翻訳結果が空の場合、元のTalentの値がフォールバックとして使用されること.
     */
    public function testTranslateTalentUsesOriginalValuesWhenTranslationIsEmpty(): void
    {
        $talent = $this->createDummyTalent();
        $targetLanguage = Language::ENGLISH;

        $googleTranslateClient = $this->createGoogleTranslateClientMock([]);

        $service = new GoogleTranslationService($googleTranslateClient);
        $result = $service->translateTalent($talent, $targetLanguage);

        $this->assertSame((string) $talent->name(), $result->translatedName());
        $this->assertSame((string) $talent->realName(), $result->translatedRealName());
        $this->assertSame((string) $talent->career(), $result->translatedCareer());
    }

    /**
     * 異常系：翻訳結果の一部が欠けている場合、欠けている項目は元の値が使用されること.
     */
    public function testTranslateTalentUsesOriginalValuesForMissingTranslations(): void
    {
        $talent = $this->createDummyTalent();
        $targetLanguage = Language::ENGLISH;

        // Only name is translated, realName and career are missing
        $googleTranslateClient = $this->createGoogleTranslateClientMock(['Chaeyoung']);

        $service = new GoogleTranslationService($googleTranslateClient);
        $result = $service->translateTalent($talent, $targetLanguage);

        $this->assertSame('Chaeyoung', $result->translatedName());
        $this->assertSame((string) $talent->realName(), $result->translatedRealName());
        $this->assertSame((string) $talent->career(), $result->translatedCareer());
    }

    /**
     * 異常系：翻訳結果の一部（name、realName）のみがある場合、careerは元の値が使用されること.
     */
    public function testTranslateTalentUsesOriginalCareerWhenMissing(): void
    {
        $talent = $this->createDummyTalent();
        $targetLanguage = Language::ENGLISH;

        // name and realName are translated, career is missing
        $googleTranslateClient = $this->createGoogleTranslateClientMock(['Chaeyoung', 'Son Chaeyoung']);

        $service = new GoogleTranslationService($googleTranslateClient);
        $result = $service->translateTalent($talent, $targetLanguage);

        $this->assertSame('Chaeyoung', $result->translatedName());
        $this->assertSame('Son Chaeyoung', $result->translatedRealName());
        $this->assertSame((string) $talent->career(), $result->translatedCareer());
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

    private function createDummyTalent(): Talent
    {
        return new Talent(
            new TalentIdentifier(StrTestHelper::generateUuid()),
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            new Slug('chaeyoung'),
            Language::KOREAN,
            new TalentName('채영'),
            new RealName('손채영'),
            new AgencyIdentifier(StrTestHelper::generateUuid()),
            [new GroupIdentifier(StrTestHelper::generateUuid())],
            new Birthday(new DateTimeImmutable('1999-04-23')),
            new Career('### 채영은 트와이스의 멤버입니다.'),
            new Version(1),
        );
    }
}
