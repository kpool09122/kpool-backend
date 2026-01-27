<?php

declare(strict_types=1);

namespace Tests\Wiki\Group\Infrastructure\Service;

use Application\Http\Client\GoogleTranslateClient\GoogleTranslateClient;
use Application\Http\Client\GoogleTranslateClient\TranslateTexts\TranslateTextsRequest;
use Application\Http\Client\GoogleTranslateClient\TranslateTexts\TranslateTextsResponse;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Group\Domain\Entity\Group;
use Source\Wiki\Group\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Group\Domain\ValueObject\Description;
use Source\Wiki\Group\Domain\ValueObject\GroupName;
use Source\Wiki\Group\Infrastructure\Service\GoogleTranslationService;
use Source\Wiki\Shared\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Shared\Domain\ValueObject\Version;
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
    public function testTranslateGroupReturnsTranslatedData(): void
    {
        $group = $this->createDummyGroup();
        $targetLanguage = Language::ENGLISH;

        $expectedTranslations = [
            'TWICE',
            '### TWICE',
        ];

        $googleTranslateClient = $this->createGoogleTranslateClientMock($expectedTranslations);

        $service = new GoogleTranslationService($googleTranslateClient);
        $result = $service->translateGroup($group, $targetLanguage);

        $this->assertSame('TWICE', $result->translatedName());
        $this->assertSame('### TWICE', $result->translatedDescription());
    }

    /**
     * 正常系：GoogleTranslateClientに正しいパラメータが渡されること.
     */
    public function testTranslateGroupCallsClientWithCorrectParameters(): void
    {
        $group = $this->createDummyGroup();
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
                'TWICE',
                '### TWICE：世界を魅了する9人組ガールズグループ',
            ]));

        $service = new GoogleTranslationService($googleTranslateClient);
        $service->translateGroup($group, $targetLanguage);

        $this->assertNotNull($capturedRequest);
        $this->assertSame([
            (string) $group->name(),
            (string) $group->description(),
        ], $capturedRequest->texts());
        $this->assertSame($targetLanguage->value, $capturedRequest->targetLanguage());
    }

    /**
     * 異常系：翻訳結果が空の場合、元のGroupの値がフォールバックとして使用されること.
     */
    public function testTranslateGroupUsesOriginalValuesWhenTranslationIsEmpty(): void
    {
        $group = $this->createDummyGroup();
        $targetLanguage = Language::ENGLISH;

        $googleTranslateClient = $this->createGoogleTranslateClientMock([]);

        $service = new GoogleTranslationService($googleTranslateClient);
        $result = $service->translateGroup($group, $targetLanguage);

        $this->assertSame((string) $group->name(), $result->translatedName());
        $this->assertSame((string) $group->description(), $result->translatedDescription());
    }

    /**
     * 異常系：翻訳結果の一部が欠けている場合、欠けている項目は元の値が使用されること.
     */
    public function testTranslateGroupUsesOriginalValuesForMissingTranslations(): void
    {
        $group = $this->createDummyGroup();
        $targetLanguage = Language::ENGLISH;

        // Only name is translated, description is missing
        $googleTranslateClient = $this->createGoogleTranslateClientMock(['TWICE']);

        $service = new GoogleTranslationService($googleTranslateClient);
        $result = $service->translateGroup($group, $targetLanguage);

        $this->assertSame('TWICE', $result->translatedName());
        $this->assertSame((string) $group->description(), $result->translatedDescription());
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

    private function createDummyGroup(): Group
    {
        return new Group(
            new GroupIdentifier(StrTestHelper::generateUuid()),
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            new Slug('twice'),
            Language::KOREAN,
            new GroupName('트와이스'),
            '트와이스',
            new AgencyIdentifier(StrTestHelper::generateUuid()),
            new Description('### 트와이스: 전 세계를 사로잡은 9인조 걸그룹'),
            new Version(1),
        );
    }
}
