<?php

declare(strict_types=1);

namespace Tests\Account\Account\Application\EventHandler;

use Application\Jobs\SendAccountConflictNotificationJob;
use Illuminate\Support\Facades\Bus;
use Source\Account\Account\Application\EventHandler\AccountCreationConflictedHandler;
use Source\Account\Account\Domain\Event\AccountCreationConflicted;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\Language;
use Tests\TestCase;

class AccountCreationConflictedHandlerTest extends TestCase
{
    public function test__construct(): void
    {
        $handler = $this->app->make(AccountCreationConflictedHandler::class);

        $this->assertInstanceOf(AccountCreationConflictedHandler::class, $handler);
    }

    public function testHandleDispatchesSendAccountConflictNotificationJob(): void
    {
        Bus::fake();

        $email = new Email('user@example.com');
        $language = Language::JAPANESE;
        $event = new AccountCreationConflicted(
            email: $email,
            language: $language,
        );

        $handler = $this->app->make(AccountCreationConflictedHandler::class);
        $handler->handle($event);

        Bus::assertDispatched(
            SendAccountConflictNotificationJob::class,
            static fn (SendAccountConflictNotificationJob $job): bool => self::jobProperty($job, 'email') == $email
                && self::jobProperty($job, 'language') === $language
        );
    }

    private static function jobProperty(SendAccountConflictNotificationJob $job, string $name): mixed
    {
        $property = new \ReflectionProperty($job, $name);

        return $property->getValue($job);
    }
}
