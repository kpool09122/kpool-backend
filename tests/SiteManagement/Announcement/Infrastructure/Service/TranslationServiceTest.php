<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Announcement\Infrastructure\Service;

use Application\Http\Client\GoogleTranslateClient\GoogleTranslateClient;
use Application\Http\Client\GoogleTranslateClient\TranslateTexts\TranslateTextsRequest;
use Application\Http\Client\GoogleTranslateClient\TranslateTexts\TranslateTextsResponse;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Source\Shared\Domain\ValueObject\Language;
use Source\SiteManagement\Announcement\Application\Service\TranslationServiceInterface;
use Tests\SiteManagement\Announcement\AnnouncementTestCase;
use UnexpectedValueException;

class TranslationServiceTest extends AnnouncementTestCase
{
    public function testContainerResolvesAdapterAndTranslatesTitleAndBodyTogether(): void
    {
        $client = Mockery::mock(GoogleTranslateClient::class);
        $client->shouldReceive('translateTexts')->once()->withArgs(static fn (TranslateTextsRequest $request): bool => $request->texts() === ['ja title', 'ja body'] && $request->targetLanguage() === 'ko')->andReturn(new TranslateTextsResponse(['한국어 제목', '한국어 본문']));
        $this->app()->instance(GoogleTranslateClient::class, $client);
        $translated = $this->app()->make(TranslationServiceInterface::class)->translateAnnouncement($this->localized(Language::JAPANESE), Language::KOREAN);
        $this->assertSame(Language::KOREAN, $translated->language);
        $this->assertSame('한국어 제목', (string) $translated->title);
        $this->assertSame('한국어 본문', (string) $translated->content);
    }

    /** @param list<string> $texts */
    #[DataProvider('invalidResponses')]
    public function testRejectsMissingOrInvalidTranslations(array $texts): void
    {
        $client = Mockery::mock(GoogleTranslateClient::class);
        $client->shouldReceive('translateTexts')->once()->andReturn(new TranslateTextsResponse($texts));
        $this->app()->instance(GoogleTranslateClient::class, $client);
        $this->expectException(UnexpectedValueException::class);
        $this->app()->make(TranslationServiceInterface::class)->translateAnnouncement($this->localized(Language::JAPANESE), Language::ENGLISH);
    }

    /** @return iterable<string, array{list<string>}> */
    public static function invalidResponses(): iterable
    {
        yield 'provider failure' => [[]];
        yield 'missing body' => [['title']];
        yield 'blank title' => [[' ', 'body']];
        yield 'blank body' => [['title', ' ']];
        yield 'long title' => [[str_repeat('a', 65), 'body']];
        yield 'long body' => [['title', str_repeat('a', 2049)]];
        yield 'unexpected extra text' => [['title', 'body', 'extra']];
    }
}
