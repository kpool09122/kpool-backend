<?php

declare(strict_types=1);

namespace Tests\Account\Account\Application\EventHandler;

use Application\Jobs\SendAccountAuthCodeJob;
use Illuminate\Support\Facades\Bus;
use Source\Account\Account\Application\EventHandler\AccountCreatedHandler;
use Source\Account\Account\Domain\Event\AccountCreated;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class AccountCreatedHandlerTest extends TestCase
{
    public function test__construct(): void
    {
        $handler = $this->app->make(AccountCreatedHandler::class);

        $this->assertInstanceOf(AccountCreatedHandler::class, $handler);
    }

    public function testHandleDispatchesSendAccountAuthCodeJob(): void
    {
        Bus::fake();

        $email = new Email('user@example.com');
        $language = Language::JAPANESE;
        $event = new AccountCreated(
            accountIdentifier: new AccountIdentifier(StrTestHelper::generateUuid()),
            email: $email,
            identityIdentifier: null,
            language: $language,
        );

        $handler = $this->app->make(AccountCreatedHandler::class);
        $handler->handle($event);

        Bus::assertDispatched(
            SendAccountAuthCodeJob::class,
            static fn (SendAccountAuthCodeJob $job): bool => self::jobProperty($job, 'email') == $email
                && self::jobProperty($job, 'language') === $language
        );
    }

    public function testHandleDoesNotDispatchJobWhenAccountAlreadyHasIdentity(): void
    {
        Bus::fake();

        $event = new AccountCreated(
            accountIdentifier: new AccountIdentifier(StrTestHelper::generateUuid()),
            email: new Email('user@example.com'),
            identityIdentifier: new IdentityIdentifier(StrTestHelper::generateUuid()),
            language: Language::ENGLISH,
        );

        $handler = $this->app->make(AccountCreatedHandler::class);
        $handler->handle($event);

        Bus::assertNotDispatched(SendAccountAuthCodeJob::class);
    }

    private static function jobProperty(SendAccountAuthCodeJob $job, string $name): mixed
    {
        $property = new \ReflectionProperty($job, $name);

        return $property->getValue($job);
    }
}
