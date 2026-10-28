<?php

declare(strict_types=1);

namespace Tests\Wiki\VideoLink\Infrastructure\Service;

use Application\Mail\VideoWatchRequestDigestMail;
use Illuminate\Support\Facades\Mail;
use Source\Shared\Application\Service\AccountNotificationRecipient;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\VideoLink\Application\Service\VideoWatchRequestDigest;
use Source\Wiki\VideoLink\Infrastructure\Service\VideoWatchRequestDigestMailService;
use Tests\TestCase;

class VideoWatchRequestDigestMailServiceTest extends TestCase
{
    public function testSendsRecipientLanguageAndLinksToWikiLanguage(): void
    {
        Mail::fake();
        config(['app.frontend_url' => 'https://k-pool.test/']);
        $recipient = new AccountNotificationRecipient(new Email('owner@example.com'), Language::JAPANESE);
        $videos = [['wikiTitle' => 'Sample', 'videoTitle' => 'Video', 'thumbnailUrl' => null, 'likeCount' => 3, 'requestCount' => 2]];
        $digest = new VideoWatchRequestDigest(new AccountIdentifier('019c9b4c-0000-7000-8000-000000000001'), Language::KOREAN, 'tl-sample', $videos, 10, 2);
        (new VideoWatchRequestDigestMailService())->send($recipient, $digest);
        Mail::assertSentCount(1);
        Mail::assertSent(VideoWatchRequestDigestMail::class, static function (VideoWatchRequestDigestMail $mail) use ($videos): bool {
            self::assertSame(Language::JAPANESE, $mail->language);
            self::assertSame('https://k-pool.test/ko/wiki/tl-sample?tab=video', $mail->videosUrl);
            self::assertSame($videos, $mail->videos);
            self::assertSame(10, $mail->recentRequestCount);
            self::assertSame(2, $mail->newRequestCount);

            return $mail->hasTo('owner@example.com');
        });
    }
}
