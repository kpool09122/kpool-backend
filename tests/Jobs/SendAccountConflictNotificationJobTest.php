<?php

declare(strict_types=1);

namespace Tests\Jobs;

use Application\Jobs\SendAccountConflictNotificationJob;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Source\Identity\Application\Service\AuthCodeSendingRateLimitServiceInterface;
use Source\Identity\Application\Service\EmailSendingStatus;
use Source\Identity\Domain\Service\AuthCodeServiceInterface;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\Language;
use Tests\TestCase;

class SendAccountConflictNotificationJobTest extends TestCase
{
    #[DataProvider('sendingStatusProvider')]
    public function testHandleUsesTheSharedLimitBeforeNotifying(bool $allowed): void
    {
        $email = new Email('user@example.com');
        $language = Language::KOREAN;
        $job = new SendAccountConflictNotificationJob($email, $language);
        /** @var AuthCodeServiceInterface&MockInterface $authCodeService */
        $authCodeService = Mockery::mock(AuthCodeServiceInterface::class);
        $allowed
            ? $authCodeService->shouldReceive('notifyConflict')->once()->with($email, $language)
            : $authCodeService->shouldNotReceive('notifyConflict');
        /** @var AuthCodeSendingRateLimitServiceInterface&MockInterface $limit */
        $limit = Mockery::mock(AuthCodeSendingRateLimitServiceInterface::class);
        $limit->shouldReceive('reserve')->once()->with($email)->andReturn(new EmailSendingStatus($allowed, $allowed ? 4 : 0, 60));

        $job->handle($authCodeService, $limit);
    }

    /** @return array<array{bool}> */
    public static function sendingStatusProvider(): array
    {
        return [[true], [false]];
    }
}
