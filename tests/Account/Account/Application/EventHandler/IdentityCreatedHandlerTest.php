<?php

declare(strict_types=1);

namespace Tests\Account\Account\Application\EventHandler;

use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Account\Account\Application\EventHandler\IdentityCreatedHandler;
use Source\Account\Account\Application\UseCase\Command\CreateAccount\CreateAccountInputPort;
use Source\Account\Account\Application\UseCase\Command\CreateAccount\CreateAccountInterface;
use Source\Account\Account\Domain\Entity\Account;
use Source\Account\Account\Domain\ValueObject\AccountName;
use Source\Account\Account\Domain\ValueObject\AccountStatus;
use Source\Account\Account\Domain\ValueObject\AccountType;
use Source\Account\Account\Domain\ValueObject\DeletionReadinessChecklist;
use Source\Account\Shared\Domain\ValueObject\AccountCategory;
use Source\Identity\Domain\Event\IdentityCreated;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
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

        $expectedAccount = new Account(
            new AccountIdentifier(StrTestHelper::generateUuid()),
            $email,
            $accountType,
            new AccountName($name),
            null,
            AccountStatus::ACTIVE,
            AccountCategory::GENERAL,
            DeletionReadinessChecklist::ready(),
        );

        $createAccount = Mockery::mock(CreateAccountInterface::class);
        $createAccount->shouldReceive('process')
            ->once()
            ->with(Mockery::on(static function (CreateAccountInputPort $input) use ($email, $accountType, $name, $identityIdentifier) {
                return (string) $input->email() === (string) $email
                    && $input->accountType() === $accountType
                    && (string) $input->accountName() === $name
                    && (string) $input->identityIdentifier() === (string) $identityIdentifier;
            }))
            ->andReturn($expectedAccount);

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

        $expectedAccount = new Account(
            new AccountIdentifier(StrTestHelper::generateUuid()),
            $email,
            $accountType,
            new AccountName('My Account'),
            null,
            AccountStatus::ACTIVE,
            AccountCategory::GENERAL,
            DeletionReadinessChecklist::ready(),
        );

        $createAccount = Mockery::mock(CreateAccountInterface::class);
        $createAccount->shouldReceive('process')
            ->once()
            ->with(Mockery::on(static function (CreateAccountInputPort $input) use ($email, $accountType, $identityIdentifier) {
                return (string) $input->email() === (string) $email
                    && $input->accountType() === $accountType
                    && (string) $input->accountName() === 'My Account'
                    && (string) $input->identityIdentifier() === (string) $identityIdentifier;
            }))
            ->andReturn($expectedAccount);

        $this->app->instance(CreateAccountInterface::class, $createAccount);
        $handler = $this->app->make(IdentityCreatedHandler::class);
        $handler->handle($event);
    }
}
