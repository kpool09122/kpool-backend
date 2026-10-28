<?php

declare(strict_types=1);

namespace Tests\Wiki\VideoLink\Application\UseCase\Command\SendVideoWatchRequestDigest;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Source\Shared\Application\Service\AccountNotificationRecipient;
use Source\Shared\Application\Service\AccountNotificationRecipientServiceInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\VideoLink\Application\Service\VideoWatchRequestDigest;
use Source\Wiki\VideoLink\Application\Service\VideoWatchRequestDigestMailServiceInterface;
use Source\Wiki\VideoLink\Application\Service\VideoWatchRequestDigestQueryServiceInterface;
use Source\Wiki\VideoLink\Application\UseCase\Command\SendVideoWatchRequestDigest\SendVideoWatchRequestDigest;
use Source\Wiki\VideoLink\Application\UseCase\Command\SendVideoWatchRequestDigest\SendVideoWatchRequestDigestInput;
use Source\Wiki\VideoLink\Application\UseCase\Command\SendVideoWatchRequestDigest\SendVideoWatchRequestDigestOutput;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class SendVideoWatchRequestDigestUnitTest extends TestCase
{
    /** @return iterable<string, array{string}> */
    public static function executionTimes(): iterable
    {
        yield '09:00 KST' => ['2026-10-28T09:00:00+09:00'];
        yield 'delayed in UTC' => ['2026-10-28T00:10:00+00:00'];
        yield 'before next 09:00 KST' => ['2026-10-29T08:59:59+09:00'];
    }

    #[DataProvider('executionTimes')]
    public function testPassesFixedPeriodsToQueryWithoutInfrastructureDependencies(string $executedAt): void
    {
        $queryService = $this->createMock(VideoWatchRequestDigestQueryServiceInterface::class);
        $queryService->expects(self::once())->method('findByPeriod')->willReturnCallback(static function (DateTimeImmutable $newSince, DateTimeImmutable $recentSince, DateTimeImmutable $before): iterable {
            self::assertSame('2026-10-27T00:00:00+00:00', $newSince->format('c'));
            self::assertSame('2026-10-21T00:00:00+00:00', $recentSince->format('c'));
            self::assertSame('2026-10-28T00:00:00+00:00', $before->format('c'));

            return [];
        });
        $recipientService = $this->createMock(AccountNotificationRecipientServiceInterface::class);
        $recipientService->expects(self::never())->method('resolveActive');
        $mailService = $this->createMock(VideoWatchRequestDigestMailServiceInterface::class);
        $mailService->expects(self::never())->method('send');
        $useCase = new SendVideoWatchRequestDigest($queryService, $recipientService, $mailService, $this->createStub(LoggerInterface::class));
        $output = new SendVideoWatchRequestDigestOutput();
        $useCase->process(new SendVideoWatchRequestDigestInput(new DateTimeImmutable($executedAt)), $output);
        self::assertSame(0, $output->failedCount);
    }

    public function testReportsFailureAndContinuesOtherRecipientsWithoutLoggingProviderSecrets(): void
    {
        $owner = new AccountIdentifier(StrTestHelper::generateUuid());
        $digest = new VideoWatchRequestDigest($owner, Language::KOREAN, 'tl-sample', [
            ['wikiTitle' => 'Sample', 'videoTitle' => 'Video', 'thumbnailUrl' => null, 'likeCount' => 1, 'requestCount' => 1],
        ], 3, 1);
        $bad = new AccountNotificationRecipient(new Email('bad@example.com'), Language::ENGLISH);
        $good = new AccountNotificationRecipient(new Email('good@example.com'), Language::JAPANESE);
        $queryService = $this->createStub(VideoWatchRequestDigestQueryServiceInterface::class);
        $queryService->method('findByPeriod')->willReturn([$digest]);
        $recipientService = $this->createMock(AccountNotificationRecipientServiceInterface::class);
        $recipientService->expects(self::once())->method('resolveActive')->with([$owner])->willReturn(['bad' => $bad, 'good' => $good]);
        $recipients = [];
        $mailService = $this->createMock(VideoWatchRequestDigestMailServiceInterface::class);
        $mailService->expects(self::exactly(2))->method('send')->willReturnCallback(static function (AccountNotificationRecipient $recipient, VideoWatchRequestDigest $actualDigest) use (&$recipients, $digest): void {
            self::assertSame($digest, $actualDigest);
            $recipients[] = (string) $recipient->email();
            if ((string) $recipient->email() === 'bad@example.com') {
                throw new RuntimeException('provider secret');
            }
        });
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('error')->with('Video watch request digest delivery failed', ['exception_type' => RuntimeException::class]);
        $output = new SendVideoWatchRequestDigestOutput();
        (new SendVideoWatchRequestDigest($queryService, $recipientService, $mailService, $logger))->process(
            new SendVideoWatchRequestDigestInput(new DateTimeImmutable('2026-10-28T09:00:00+09:00')),
            $output,
        );
        self::assertSame(1, $output->failedCount);
        self::assertSame(['bad@example.com', 'good@example.com'], $recipients);
    }
}
