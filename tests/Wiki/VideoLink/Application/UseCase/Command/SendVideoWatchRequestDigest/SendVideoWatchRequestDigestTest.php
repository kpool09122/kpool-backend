<?php

declare(strict_types=1);

namespace Tests\Wiki\VideoLink\Application\UseCase\Command\SendVideoWatchRequestDigest;

use Application\Mail\VideoWatchRequestDigestMail;
use Application\Models\Wiki\VideoWatchRequest;
use Application\Models\Wiki\Wiki;
use Carbon\CarbonImmutable;
use Illuminate\Mail\Mailer;
use Illuminate\Mail\Transport\ArrayTransport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use RuntimeException;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\VideoLink\Application\UseCase\Command\SendVideoWatchRequestDigest\SendVideoWatchRequestDigestInput;
use Source\Wiki\VideoLink\Application\UseCase\Command\SendVideoWatchRequestDigest\SendVideoWatchRequestDigestInterface;
use Source\Wiki\VideoLink\Application\UseCase\Command\SendVideoWatchRequestDigest\SendVideoWatchRequestDigestOutput;
use Tests\Helper\CreateAccount;
use Tests\Helper\CreateIdentity;
use Tests\Helper\CreatePrincipal;
use Tests\Helper\CreateVideoLink;
use Tests\Helper\CreateWiki;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class SendVideoWatchRequestDigestTest extends TestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        CarbonImmutable::setTestNow('2026-10-28 00:00:00 UTC');
    }

    #[Override]
    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    /** @return iterable<string, array{string}> */
    public static function executionTimes(): iterable
    {
        yield 'scheduled at 09:00 KST' => ['2026-10-28 00:00:00 UTC'];
        yield 'queue delayed ten minutes' => ['2026-10-28 00:10:00 UTC'];
        yield 'before next 09:00 KST' => ['2026-10-28 23:59:59 UTC'];
    }

    #[DataProvider('executionTimes')]
    public function testAggregatesFixedSeoulNineOClockPeriodsAndSevenDays(string $executionTime): void
    {
        Mail::fake();
        CarbonImmutable::setTestNow($executionTime);
        $account = $this->account('owner@example.com', 'ko');
        $id = $this->request($account, 'Wiki', 'Before seven days', '2026-10-20 23:59:59 UTC');
        $wiki = VideoWatchRequest::query()->findOrFail($id)->wiki_id;
        $this->request($account, 'Wiki', 'Seven-day start', '2026-10-21 00:00:00 UTC', $wiki);
        $this->request($account, 'Wiki', 'Before daily start', '2026-10-26 23:59:59 UTC', $wiki);
        $this->request($account, 'Wiki', 'Daily start', '2026-10-27 00:00:00 UTC', $wiki);
        $this->request($account, 'Wiki', 'Daily end', '2026-10-27 23:59:59 UTC', $wiki);
        $this->request($account, 'Wiki', 'Next period', '2026-10-28 00:00:00 UTC', $wiki);
        self::assertSame(0, $this->sendDigest());
        Mail::assertSent(VideoWatchRequestDigestMail::class, static function (VideoWatchRequestDigestMail $mail): bool {
            self::assertSame('ko', $mail->language->value);
            self::assertSame(4, $mail->recentRequestCount);
            self::assertSame(2, $mail->newRequestCount);
            self::assertSame(['Daily start', 'Daily end'], array_column($mail->videos, 'videoTitle'));
            self::assertStringEndsWith('?tab=video', $mail->videosUrl);

            return $mail->hasTo('owner@example.com');
        });
        Mail::assertSentCount(1);

        CarbonImmutable::setTestNow('2026-10-29 00:00:00 UTC');
        Mail::fake();
        self::assertSame(0, $this->sendDigest());
        Mail::assertSent(VideoWatchRequestDigestMail::class, static function (VideoWatchRequestDigestMail $mail): bool {
            self::assertSame(4, $mail->recentRequestCount);
            self::assertSame(1, $mail->newRequestCount);
            self::assertSame(['Next period'], array_column($mail->videos, 'videoTitle'));

            return true;
        });
        self::assertSame(6, VideoWatchRequest::query()->count());
    }

    public function testFailureLeavesRequestsUnchangedAndContinuesOtherRecipients(): void
    {
        $good = $this->account('good@example.com');
        $bad = $this->account('bad@example.com', 'en');
        $this->request($good, 'Good', 'Good video', '2026-10-27 00:00:00 UTC');
        $this->request($bad, 'Bad', 'Bad video', '2026-10-27 00:00:00 UTC');
        $mailer = Mockery::mock(Mailer::class);
        $mailer->shouldReceive('to')->with('good@example.com')->once()->andReturnSelf();
        $mailer->shouldReceive('to')->with('bad@example.com')->once()->andReturnSelf();
        $mailer->shouldReceive('send')->twice()->andReturnUsing(static function (VideoWatchRequestDigestMail $mail): void {
            if ($mail->language->value === 'en') {
                throw new RuntimeException('provider secret');
            }
        });
        $manager = Mail::getFacadeRoot();
        Mail::swap($mailer);

        try {
            self::assertSame(1, $this->sendDigest());
            self::assertSame(2, VideoWatchRequest::query()->count());
        } finally {
            Mail::swap($manager);
        }
    }

    public function testDisabledAccountsMembersAndUnconfiguredRecipientsAreNotNotified(): void
    {
        Mail::fake();
        foreach (['suspended-account', 'suspended-member', 'no-recipient', 'stale-recipient', 'not-official', 'group', 'unpublished'] as $scenario) {
            $account = $this->account($scenario . '@example.com');
            $id = $this->request($account, $scenario, 'Private', '2026-10-27 00:00:00 UTC');
            $request = VideoWatchRequest::query()->findOrFail($id);
            match ($scenario) {
                'suspended-account' => DB::table('accounts')->where('id', $account)->update(['status' => 'suspended']),
                'suspended-member' => DB::table('account_principals')->where('account_id', $account)->update(['status' => 'suspended']),
                'no-recipient' => DB::table('account_notification_recipients')->where('account_id', $account)->delete(),
                'stale-recipient' => DB::table('account_principals')->where('account_id', $account)->delete(),
                'not-official' => Wiki::query()->where('id', $request->wiki_id)->update(['owner_account_id' => null]),
                'group' => Wiki::query()->where('id', $request->wiki_id)->update(['resource_type' => 'group']),
                'unpublished' => Wiki::query()->where('id', $request->wiki_id)->update(['published_at' => null]),
            };
        }
        self::assertSame(0, $this->sendDigest());
        Mail::assertNothingSent();
    }

    public function testRepeatedRequestsForDeletedVideoAreCounted(): void
    {
        Mail::fake();
        $account = $this->account('owner@example.com');
        $id = $this->request($account, 'Wiki', 'Deleted', '2026-10-27 00:00:00 UTC');
        $request = VideoWatchRequest::query()->findOrFail($id);
        DB::table('wiki_video_watch_requests')->insert(['id' => StrTestHelper::generateUuid(), 'video_link_id' => $request->video_link_id, 'wiki_id' => $request->wiki_id, 'principal_id' => StrTestHelper::generateUuid(), 'sent_at' => '2026-10-27 01:00:00']);
        DB::table('wiki_video_links')->where('id', $request->video_link_id)->delete();
        self::assertSame(0, $this->sendDigest());
        Mail::assertSent(VideoWatchRequestDigestMail::class, static function (VideoWatchRequestDigestMail $mail): bool {
            self::assertCount(1, $mail->videos);
            self::assertNull($mail->videos[0]['videoTitle']);
            self::assertNull($mail->videos[0]['thumbnailUrl']);
            self::assertSame(2, $mail->videos[0]['requestCount']);
            self::assertSame(2, $mail->recentRequestCount);
            self::assertSame(2, $mail->newRequestCount);

            return true;
        });
    }

    public function testOneIdentityAcrossTwoOwnerAccountsGetsSeparateWikiMailsWithoutAnotherOwnersData(): void
    {
        Mail::fake();
        $first = $this->account('shared@example.com');
        $second = $this->account('unused@example.com');
        $other = $this->account('other@example.com');
        $identity = DB::table('identities')->where('email', 'shared@example.com')->value('id');
        DB::table('account_principals')->where('account_id', $second)->update(['identity_id' => $identity]);
        DB::table('account_notification_recipients')->where('account_id', $second)->update(['identity_id' => $identity]);
        $this->request($first, 'First', 'A', '2026-10-27 00:00:00 UTC');
        $this->request($second, 'Second', 'B', '2026-10-27 00:00:00 UTC');
        $this->request($other, 'Other', 'C', '2026-10-27 00:00:00 UTC');
        self::assertSame(0, $this->sendDigest());
        Mail::assertSentCount(3);
        $sharedMails = Mail::sent(VideoWatchRequestDigestMail::class, static fn (VideoWatchRequestDigestMail $mail): bool => $mail->hasTo('shared@example.com'));
        self::assertCount(2, $sharedMails);
        $wikiTitles = [];
        foreach ($sharedMails as $mail) {
            self::assertInstanceOf(VideoWatchRequestDigestMail::class, $mail);
            self::assertCount(1, $mail->videos);
            self::assertSame(1, $mail->newRequestCount);
            self::assertSame(1, $mail->recentRequestCount);
            $wikiTitles[] = $mail->videos[0]['wikiTitle'];
        }
        sort($wikiTitles);
        self::assertSame(['First', 'Second'], $wikiTitles);
        Mail::assertSent(VideoWatchRequestDigestMail::class, static fn (VideoWatchRequestDigestMail $mail): bool => $mail->hasTo('other@example.com') && array_column($mail->videos, 'wikiTitle') === ['Other']);
    }

    public function testRealArrayTransportRendersMail(): void
    {
        $this->app()->make('view')->addLocation(dirname(__DIR__, 7) . '/resources/views');
        config(['mail.default' => 'array']);
        $account = $this->account('real@example.com');
        $this->request($account, 'Real Wiki', 'Real Video', '2026-10-27 00:00:00 UTC');
        self::assertSame(0, $this->sendDigest());
        $transport = Mail::mailer()->getSymfonyTransport();
        self::assertInstanceOf(ArrayTransport::class, $transport);
        self::assertCount(1, $transport->messages());
    }

    public function testNoNewRequestsSendsNoMailEvenWhenPastRequestsExist(): void
    {
        Mail::fake();
        $account = $this->account('old@example.com');
        $this->request($account, 'Old', 'Previous period', '2026-10-26 23:59:59 UTC');
        self::assertSame(0, $this->sendDigest());
        Mail::assertNothingSent();
    }

    public function testFeaturesOnlyFiveNewVideosByNormalLikes(): void
    {
        Mail::fake();
        $account = $this->account('featured@example.com');
        $wiki = null;
        foreach ([1, 4, 2, 6, 5, 3, 0] as $index => $likeCount) {
            $id = $this->request($account, 'Featured Wiki', 'Video ' . $index, '2026-10-27 00:00:00 UTC', $wiki);
            $request = VideoWatchRequest::query()->findOrFail($id);
            $wiki = $request->wiki_id;
            DB::table('wiki_video_links')->where('id', $request->video_link_id)->update(['thumbnail_url' => 'https://i.ytimg.com/vi/abcdefghijk/hqdefault.jpg']);
            for ($like = 0; $like < $likeCount; $like++) {
                $principal = $this->principal();
                DB::table('wiki_video_link_likes')->insert(['id' => StrTestHelper::generateUuid(), 'video_link_id' => $request->video_link_id, 'principal_id' => $principal, 'is_official_owner' => false, 'created_at' => now()]);
            }
            if ($index === 2) {
                $principal = $this->principal();
                DB::table('wiki_video_link_likes')->insert(['id' => StrTestHelper::generateUuid(), 'video_link_id' => $request->video_link_id, 'principal_id' => $principal, 'is_official_owner' => true, 'created_at' => now()]);
            }
            if ($index === 3) {
                DB::table('wiki_video_links')->where('id', $request->video_link_id)->update(['thumbnail_path' => 'images/featured.webp']);
                DB::table('wiki_video_watch_requests')->insert(['id' => StrTestHelper::generateUuid(), 'video_link_id' => $request->video_link_id, 'wiki_id' => $request->wiki_id, 'principal_id' => StrTestHelper::generateUuid(), 'sent_at' => '2026-10-27 01:00:00']);
            }
            if ($index === 4) {
                DB::table('wiki_video_links')->where('id', $request->video_link_id)->update(['thumbnail_url' => 'https://tracking.example/video.webp']);
            }
        }
        self::assertSame(0, $this->sendDigest());
        Mail::assertSent(VideoWatchRequestDigestMail::class, static function (VideoWatchRequestDigestMail $mail): bool {
            self::assertSame(['Video 3', 'Video 4', 'Video 1', 'Video 5', 'Video 2'], array_column($mail->videos, 'videoTitle'));
            self::assertSame([6, 5, 4, 3, 2], array_column($mail->videos, 'likeCount'));
            self::assertSame(8, $mail->recentRequestCount);
            self::assertSame(8, $mail->newRequestCount);
            self::assertSame(2, $mail->videos[0]['requestCount']);
            self::assertStringContainsString('images/featured.webp', $mail->videos[0]['thumbnailUrl'] ?? '');
            self::assertNull($mail->videos[1]['thumbnailUrl']);
            self::assertSame('https://i.ytimg.com/vi/abcdefghijk/hqdefault.jpg', $mail->videos[2]['thumbnailUrl']);
            self::assertStringContainsString('/ko/wiki/tl-', $mail->videosUrl);
            self::assertStringEndsWith('?tab=video', $mail->videosUrl);

            return true;
        });
    }

    public function testRecipientFilterOnlySendsTheMatchingOwnersDigest(): void
    {
        Mail::fake();
        $owner = $this->account('owner@example.com');
        $other = $this->account('other@example.com');
        $this->request($owner, 'Owner Wiki', 'Owner Video', '2026-10-27 00:00:00 UTC');
        $this->request($other, 'Other Wiki', 'Other Video', '2026-10-27 00:00:00 UTC');
        self::assertSame(0, $this->sendDigest(new Email('OWNER@example.com')));
        Mail::assertSentCount(1);
        Mail::assertSent(VideoWatchRequestDigestMail::class, static function (VideoWatchRequestDigestMail $mail): bool {
            self::assertSame(['Owner Video'], array_column($mail->videos, 'videoTitle'));

            return $mail->hasTo('owner@example.com');
        });
    }

    public function testRecipientFilterDoesNotSendToUnconfiguredAddresses(): void
    {
        Mail::fake();
        $owner = $this->account('owner@example.com');
        $this->request($owner, 'Wiki', 'Video', '2026-10-27 00:00:00 UTC');
        self::assertSame(0, $this->sendDigest(new Email('unconfigured@example.com')));
        Mail::assertNothingSent();
    }

    /** @phpstan-impure */
    private function sendDigest(?Email $recipientEmail = null): int
    {
        $output = new SendVideoWatchRequestDigestOutput();
        $this->app()->make(SendVideoWatchRequestDigestInterface::class)->process(
            new SendVideoWatchRequestDigestInput(CarbonImmutable::now()->toDateTimeImmutable(), $recipientEmail),
            $output,
        );

        return $output->failedCount;
    }

    private function principal(): string
    {
        $identity = new IdentityIdentifier(StrTestHelper::generateUuid());
        $principal = new PrincipalIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($identity, ['email' => (string) $identity . '@example.com']);
        CreatePrincipal::create($principal, $identity);

        return (string) $principal;
    }

    private function account(string $email, string $language = 'ja'): string
    {
        $account = StrTestHelper::generateUuid();
        $identity = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateAccount::create($account);
        CreateIdentity::create($identity, ['email' => $email, 'language' => $language]);
        DB::table('account_principals')->insert(['id' => StrTestHelper::generateUuid(), 'account_id' => $account, 'identity_id' => (string) $identity, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('account_notification_recipients')->insert(['account_id' => $account, 'identity_id' => (string) $identity]);

        return $account;
    }

    private function request(string $account, string $wikiTitle, string $videoTitle, string $sentAt, ?string $wikiId = null): string
    {
        $wiki = $wikiId ?? StrTestHelper::generateUuid();
        $video = StrTestHelper::generateUuid();
        $request = StrTestHelper::generateUuid();
        if ($wikiId === null) {
            CreateWiki::create($wiki, 'talent', ['slug' => 'tl-' . $wiki, 'owner_account_id' => $account, 'title' => $wikiTitle, 'published_at' => '2026-01-01']);
        }
        CreateVideoLink::create($video, ['wiki_id' => $wiki, 'title' => $videoTitle]);
        DB::table('wiki_video_watch_requests')->insert(['id' => $request, 'video_link_id' => $video, 'wiki_id' => $wiki, 'principal_id' => StrTestHelper::generateUuid(), 'sent_at' => CarbonImmutable::parse($sentAt)]);

        return $request;
    }
}
