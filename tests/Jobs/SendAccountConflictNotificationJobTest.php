<?php

declare(strict_types=1);

namespace Tests\Jobs;

use Application\Jobs\SendAccountConflictNotificationJob;
use Mockery;
use Source\Identity\Domain\Service\AuthCodeServiceInterface;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\Language;
use Tests\TestCase;

class SendAccountConflictNotificationJobTest extends TestCase
{
    public function testHandleCallsNotifyConflict(): void
    {
        $email = new Email('user@example.com');
        $language = Language::KOREAN;
        $job = new SendAccountConflictNotificationJob($email, $language);

        /** @var AuthCodeServiceInterface&\Mockery\MockInterface $authCodeService */
        $authCodeService = Mockery::mock(AuthCodeServiceInterface::class);
        $authCodeService->shouldReceive('notifyConflict')
            ->once()
            ->with($email, $language);

        $job->handle($authCodeService);
    }
}
