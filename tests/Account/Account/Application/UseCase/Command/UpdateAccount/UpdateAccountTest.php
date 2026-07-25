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
use Source\Account\Account\Domain\ValueObject\AccountName;
use Source\Account\Account\Domain\ValueObject\AccountStatus;
use Source\Account\Account\Domain\ValueObject\AccountType;
use Source\Account\Account\Domain\ValueObject\DeletionReadinessChecklist;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Account\Principal\Domain\ValueObject\Action;
use Source\Account\Principal\Domain\ValueObject\Resource;
use Source\Account\Shared\Domain\ValueObject\AccountCategory;
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
            ->with($principal, Action::UPDATE, Mockery::type(Resource::class))
            ->andReturnTrue();

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

    private function createAccount(): Account
    {
        return new Account(
            new AccountIdentifier(StrTestHelper::generateUuid()),
            new Email('test@example.com'),
            AccountType::CORPORATION,
            new AccountName('Example Inc'),
            AccountStatus::ACTIVE,
            AccountCategory::GENERAL,
            DeletionReadinessChecklist::ready(),
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
