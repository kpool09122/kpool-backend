<?php

declare(strict_types=1);

namespace Tests\Account\Account\Application\EventHandler;

use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Account\Account\Application\EventHandler\IdentityCreatedHandler;
use Source\Account\Account\Application\UseCase\Command\CreateAccount\CreateAccountInputPort;
use Source\Account\Account\Application\UseCase\Command\CreateAccount\CreateAccountInterface;
use Source\Account\Account\Application\UseCase\Command\CreateAccount\CreateAccountOutputPort;
use Source\Account\Account\Domain\ValueObject\AccountType;
use Source\Identity\Domain\Event\IdentityCreated;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class IdentityCreatedHandlerTest extends TestCase
{
    /**
     * 正常系: IdentityCreatedイベントでCreateAccountが呼ばれること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testHandleCallsCreateAccount(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $email = new Email('test@example.com');
        $accountType = AccountType::INDIVIDUAL;
        $name = 'Test User';

        $event = new IdentityCreated(
            identityIdentifier: $identityIdentifier,
            email: $email,
            accountType: $accountType,
            name: $name,
        );

        $createAccount = Mockery::mock(CreateAccountInterface::class);
        $createAccount->shouldReceive('process')
            ->once()
            ->with(
                Mockery::on(static fn (CreateAccountInputPort $input) => (string) $input->email() === (string) $email
                    && $input->accountType() === $accountType
                    && (string) $input->accountName() === $name
                    && (string) $input->identityIdentifier() === (string) $identityIdentifier),
                Mockery::type(CreateAccountOutputPort::class),
            )
            ->andReturnNull();

        $this->app->instance(CreateAccountInterface::class, $createAccount);
        $handler = $this->app->make(IdentityCreatedHandler::class);
        $handler->handle($event);
    }

    /**
     * 正常系: nameがnullの場合、デフォルトのAccountNameが使用されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testHandleWithNullNameUsesDefaultAccountName(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $email = new Email('test@example.com');
        $accountType = AccountType::CORPORATION;

        $event = new IdentityCreated(
            identityIdentifier: $identityIdentifier,
            email: $email,
            accountType: $accountType,
            name: null,
        );

        $createAccount = Mockery::mock(CreateAccountInterface::class);
        $createAccount->shouldReceive('process')
            ->once()
            ->with(
                Mockery::on(static fn (CreateAccountInputPort $input) => (string) $input->email() === (string) $email
                    && $input->accountType() === $accountType
                    && (string) $input->accountName() === 'My Account'
                    && (string) $input->identityIdentifier() === (string) $identityIdentifier),
                Mockery::type(CreateAccountOutputPort::class),
            )
            ->andReturnNull();

        $this->app->instance(CreateAccountInterface::class, $createAccount);
        $handler = $this->app->make(IdentityCreatedHandler::class);
        $handler->handle($event);
    }
}
