<?php

declare(strict_types=1);

namespace Tests\Account\Account\Application\UseCase\Command\UpdateAccount;

use Mockery;
use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Account\Application\Exception\AccountUpdateForbiddenException;
use Source\Account\Account\Application\UseCase\Command\UpdateAccount\UpdateAccount;
use Source\Account\Account\Application\UseCase\Command\UpdateAccount\UpdateAccountInput;
use Source\Account\Account\Application\UseCase\Command\UpdateAccount\UpdateAccountInterface;
use Source\Account\Account\Application\UseCase\Command\UpdateAccount\UpdateAccountOutput;
use Source\Account\Account\Domain\Entity\Account;
use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\Account\Domain\ValueObject\AccountDocuments;
use Source\Account\Account\Domain\ValueObject\AccountName;
use Source\Account\Account\Domain\ValueObject\AccountStatus;
use Source\Account\Account\Domain\ValueObject\DeletionReadinessChecklist;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Account\Principal\Domain\ValueObject\Action;
use Source\Account\Principal\Domain\ValueObject\Resource;
use Source\Shared\Domain\ValueObject\AccountCategory;
use Source\Account\Shared\Domain\ValueObject\AccountType;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class UpdateAccountTest extends TestCase
{
    public function test__construct(): void
    {
        /** @var AccountRepositoryInterface&Mockery\MockInterface $accountRepository */
        $accountRepository = Mockery::mock(AccountRepositoryInterface::class);
        /** @var PolicyEvaluatorInterface&Mockery\MockInterface $policyEvaluator */
        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $this->app->instance(AccountRepositoryInterface::class, $accountRepository);
        $this->app->instance(PolicyEvaluatorInterface::class, $policyEvaluator);

        $useCase = $this->app->make(UpdateAccountInterface::class);

        $this->assertInstanceOf(UpdateAccount::class, $useCase);
    }

    /**
     * @throws AccountNotFoundException
     * @throws AccountUpdateForbiddenException
     */
    public function testProcessAllowsPermittedPrincipalToUpdateAccountName(): void
    {
        $account = $this->createAccount();
        $principal = $this->createPrincipal($account->accountIdentifier());
        $newName = new AccountName('Updated Account');
        $input = new UpdateAccountInput($account->accountIdentifier(), $principal, $newName);

        /** @var AccountRepositoryInterface&Mockery\MockInterface $accountRepository */
        $accountRepository = Mockery::mock(AccountRepositoryInterface::class);
        $accountRepository->shouldReceive('findById')->with($account->accountIdentifier())->once()->andReturn($account);
        $accountRepository->shouldReceive('save')->once()->with(Mockery::on(
            fn (Account $savedAccount): bool => (string) $savedAccount->name() === (string) $newName
        ));
        /** @var PolicyEvaluatorInterface&Mockery\MockInterface $policyEvaluator */
        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $policyEvaluator->shouldReceive('evaluate')
            ->once()
            ->with($principal, Action::UPDATE, Mockery::on(
                static fn (Resource $resource): bool => $resource->accountType() === AccountType::CORPORATION
            ))
            ->andReturnTrue();

        $useCase = new UpdateAccount($accountRepository, $policyEvaluator);
        $output = new UpdateAccountOutput();
        $useCase->process($input, $output);

        $this->assertSame('Updated Account', $output->toArray()['name']);
    }

    /**
     * @throws AccountNotFoundException
     * @throws AccountUpdateForbiddenException
     */
    public function testProcessBuildsContactAddressFromInputValues(): void
    {
        $account = $this->createAccount();
        $principal = $this->createPrincipal($account->accountIdentifier());
        $input = new UpdateAccountInput(
            accountIdentifier: $account->accountIdentifier(),
            principal: $principal,
            accountName: new AccountName('Updated Account'),
            addressCountryCode: 'US',
            addressAdministrativeAreaCode: 'FL',
            addressPostalCode: '33139',
            addressLocality: 'Miami Beach',
            addressLine1: '1 Ocean Dr',
            addressLine2: 'Suite 2',
        );

        /** @var AccountRepositoryInterface&Mockery\MockInterface $accountRepository */
        $accountRepository = Mockery::mock(AccountRepositoryInterface::class);
        $accountRepository->shouldReceive('findById')->with($account->accountIdentifier())->once()->andReturn($account);
        $accountRepository->shouldReceive('save')->once()->with(Mockery::on(
            static fn (Account $savedAccount): bool => $savedAccount->address()?->toArray() === [
                'countryCode' => 'US',
                'administrativeAreaCode' => 'FL',
                'postalCode' => '33139',
                'locality' => 'Miami Beach',
                'addressLine1' => '1 Ocean Dr',
                'addressLine2' => 'Suite 2',
            ]
        ));
        /** @var PolicyEvaluatorInterface&Mockery\MockInterface $policyEvaluator */
        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $policyEvaluator->shouldReceive('evaluate')->once()->andReturnTrue();

        $useCase = new UpdateAccount($accountRepository, $policyEvaluator);
        $output = new UpdateAccountOutput();
        $useCase->process($input, $output);

        $this->assertSame('Updated Account', $output->toArray()['name']);
    }

    public function testProcessThrowsAccountNotFoundException(): void
    {
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $input = new UpdateAccountInput(
            $accountIdentifier,
            $this->createPrincipal($accountIdentifier),
            new AccountName('Updated Account'),
        );

        /** @var AccountRepositoryInterface&Mockery\MockInterface $accountRepository */
        $accountRepository = Mockery::mock(AccountRepositoryInterface::class);
        $accountRepository->shouldReceive('findById')->with($accountIdentifier)->once()->andReturnNull();
        $accountRepository->shouldNotReceive('save');
        /** @var PolicyEvaluatorInterface&Mockery\MockInterface $policyEvaluator */
        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $policyEvaluator->shouldNotReceive('evaluate');

        $this->expectException(AccountNotFoundException::class);

        (new UpdateAccount($accountRepository, $policyEvaluator))->process($input, new UpdateAccountOutput());
    }

    public function testProcessThrowsForbiddenWhenPolicyDenies(): void
    {
        $account = $this->createAccount();
        $principal = $this->createPrincipal($account->accountIdentifier());
        $input = new UpdateAccountInput(
            $account->accountIdentifier(),
            $principal,
            new AccountName('Updated Account')
        );

        /** @var AccountRepositoryInterface&Mockery\MockInterface $accountRepository */
        $accountRepository = Mockery::mock(AccountRepositoryInterface::class);
        $accountRepository->shouldReceive('findById')->with($account->accountIdentifier())->once()->andReturn($account);
        $accountRepository->shouldNotReceive('save');
        /** @var PolicyEvaluatorInterface&Mockery\MockInterface $policyEvaluator */
        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $policyEvaluator->shouldReceive('evaluate')->once()->andReturnFalse();

        $this->expectException(AccountUpdateForbiddenException::class);

        (new UpdateAccount($accountRepository, $policyEvaluator))->process($input, new UpdateAccountOutput());
    }

    public function testProcessThrowsForbiddenForDifferentAccount(): void
    {
        $account = $this->createAccount();
        $input = new UpdateAccountInput(
            $account->accountIdentifier(),
            $this->createPrincipal(new AccountIdentifier(StrTestHelper::generateUuid())),
            new AccountName('Updated Account'),
        );

        /** @var AccountRepositoryInterface&Mockery\MockInterface $accountRepository */
        $accountRepository = Mockery::mock(AccountRepositoryInterface::class);
        $accountRepository->shouldReceive('findById')->with($account->accountIdentifier())->once()->andReturn($account);
        $accountRepository->shouldNotReceive('save');
        /** @var PolicyEvaluatorInterface&Mockery\MockInterface $policyEvaluator */
        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $policyEvaluator->shouldNotReceive('evaluate');

        $this->expectException(AccountUpdateForbiddenException::class);

        (new UpdateAccount($accountRepository, $policyEvaluator))->process($input, new UpdateAccountOutput());
    }

    public function testProcessAllowsIndividualAccountToUpdateOwnAccount(): void
    {
        $account = $this->createAccount(AccountType::INDIVIDUAL);
        $principal = $this->createPrincipal($account->accountIdentifier());
        $input = new UpdateAccountInput(
            $account->accountIdentifier(),
            $principal,
            new AccountName('Updated Individual Account'),
        );

        /** @var AccountRepositoryInterface&Mockery\MockInterface $accountRepository */
        $accountRepository = Mockery::mock(AccountRepositoryInterface::class);
        $accountRepository->shouldReceive('findById')->with($account->accountIdentifier())->once()->andReturn($account);
        $accountRepository->shouldReceive('save')->once()->with(Mockery::on(
            static fn (Account $savedAccount): bool => (string) $savedAccount->name() === 'Updated Individual Account'
        ));
        /** @var PolicyEvaluatorInterface&Mockery\MockInterface $policyEvaluator */
        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $policyEvaluator->shouldReceive('evaluate')
            ->once()
            ->with($principal, Action::UPDATE, Mockery::on(
                static fn (Resource $resource): bool => $resource->accountType() === AccountType::INDIVIDUAL
            ))
            ->andReturnTrue();

        $output = new UpdateAccountOutput();
        (new UpdateAccount($accountRepository, $policyEvaluator))->process($input, $output);

        $this->assertSame('Updated Individual Account', $output->toArray()['name']);
    }

    private function createAccount(AccountType $accountType = AccountType::CORPORATION): Account
    {
        return new Account(
            new AccountIdentifier(StrTestHelper::generateUuid()),
            new Email('test@example.com'),
            $accountType,
            new AccountName('Example Inc'),
            AccountStatus::ACTIVE,
            AccountCategory::GENERAL,
            DeletionReadinessChecklist::ready(),
            new AccountDocuments(),
        );
    }

    private function createPrincipal(AccountIdentifier $accountIdentifier): Principal
    {
        return new Principal(
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            $accountIdentifier,
        );
    }
}
