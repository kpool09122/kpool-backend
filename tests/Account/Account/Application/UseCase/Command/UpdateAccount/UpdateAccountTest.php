<?php

declare(strict_types=1);

namespace Tests\Account\Account\Application\UseCase\Command\UpdateAccount;

use DateTimeImmutable;
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
use Source\Account\Principal\Domain\Entity\PrincipalGroup;
use Source\Account\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Account\Principal\Domain\ValueObject\AccountRole;
use Source\Account\Shared\Domain\ValueObject\AccountCategory;
use Source\Account\Shared\Domain\ValueObject\PrincipalGroupIdentifier;
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
        /** @var PrincipalGroupRepositoryInterface&Mockery\MockInterface $principalGroupRepository */
        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $this->app->instance(AccountRepositoryInterface::class, $accountRepository);
        $this->app->instance(PrincipalGroupRepositoryInterface::class, $principalGroupRepository);

        $useCase = $this->app->make(UpdateAccountInterface::class);

        $this->assertInstanceOf(UpdateAccount::class, $useCase);
    }

    /**
     * @throws AccountNotFoundException
     * @throws AccountUpdateForbiddenException
     */
    public function testProcessAllowsOwnerToUpdateAccountName(): void
    {
        $this->assertAllowedRoleCanUpdate(AccountRole::OWNER);
    }

    /**
     * @throws AccountNotFoundException
     * @throws AccountUpdateForbiddenException
     */
    public function testProcessAllowsAdminToUpdateAccountName(): void
    {
        $this->assertAllowedRoleCanUpdate(AccountRole::ADMIN);
    }

    /**
     * @throws AccountNotFoundException
     * @throws AccountUpdateForbiddenException
     */
    private function assertAllowedRoleCanUpdate(AccountRole $role): void
    {
        $account = $this->createAccount();
        $actorIdentityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $newName = new AccountName('Updated Account');
        $input = new UpdateAccountInput($account->accountIdentifier(), $actorIdentityIdentifier, $newName);
        $principalGroup = $this->createPrincipalGroup($account->accountIdentifier(), $role, $actorIdentityIdentifier);

        /** @var AccountRepositoryInterface&Mockery\MockInterface $accountRepository */
        $accountRepository = Mockery::mock(AccountRepositoryInterface::class);
        $accountRepository->shouldReceive('findById')->with($account->accountIdentifier())->once()->andReturn($account);
        $accountRepository->shouldReceive('save')->once()->with(Mockery::on(
            fn (Account $savedAccount): bool => (string) $savedAccount->name() === (string) $newName
        ));
        /** @var PrincipalGroupRepositoryInterface&Mockery\MockInterface $principalGroupRepository */
        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $principalGroupRepository->shouldReceive('findByAccountIdAndPrincipal')
            ->with($account->accountIdentifier(), Mockery::type(Principal::class))
            ->once()
            ->andReturn([$principalGroup]);

        $useCase = new UpdateAccount($accountRepository, $principalGroupRepository);
        $output = new UpdateAccountOutput();
        $useCase->process($input, $output);

        $this->assertSame('Updated Account', $output->toArray()['name']);
    }

    public function testProcessThrowsAccountNotFoundException(): void
    {
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $input = new UpdateAccountInput(
            $accountIdentifier,
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            new AccountName('Updated Account'),
        );

        /** @var AccountRepositoryInterface&Mockery\MockInterface $accountRepository */
        $accountRepository = Mockery::mock(AccountRepositoryInterface::class);
        $accountRepository->shouldReceive('findById')->with($accountIdentifier)->once()->andReturnNull();
        $accountRepository->shouldNotReceive('save');
        /** @var PrincipalGroupRepositoryInterface&Mockery\MockInterface $principalGroupRepository */
        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $principalGroupRepository->shouldNotReceive('findByAccountIdAndPrincipal');

        $this->expectException(AccountNotFoundException::class);

        (new UpdateAccount($accountRepository, $principalGroupRepository))->process($input, new UpdateAccountOutput());
    }

    public function testProcessThrowsForbiddenForDisallowedRole(): void
    {
        $account = $this->createAccount();
        $actorIdentityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $input = new UpdateAccountInput($account->accountIdentifier(), $actorIdentityIdentifier, new AccountName('Updated Account'));
        $principalGroup = $this->createPrincipalGroup($account->accountIdentifier(), AccountRole::MEMBER, $actorIdentityIdentifier);

        /** @var AccountRepositoryInterface&Mockery\MockInterface $accountRepository */
        $accountRepository = Mockery::mock(AccountRepositoryInterface::class);
        $accountRepository->shouldReceive('findById')->with($account->accountIdentifier())->once()->andReturn($account);
        $accountRepository->shouldNotReceive('save');
        /** @var PrincipalGroupRepositoryInterface&Mockery\MockInterface $principalGroupRepository */
        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $principalGroupRepository->shouldReceive('findByAccountIdAndPrincipal')->once()->andReturn([$principalGroup]);

        $this->expectException(AccountUpdateForbiddenException::class);

        (new UpdateAccount($accountRepository, $principalGroupRepository))->process($input, new UpdateAccountOutput());
    }

    public function testProcessThrowsForbiddenForNonMember(): void
    {
        $account = $this->createAccount();
        $input = new UpdateAccountInput(
            $account->accountIdentifier(),
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            new AccountName('Updated Account'),
        );

        /** @var AccountRepositoryInterface&Mockery\MockInterface $accountRepository */
        $accountRepository = Mockery::mock(AccountRepositoryInterface::class);
        $accountRepository->shouldReceive('findById')->with($account->accountIdentifier())->once()->andReturn($account);
        $accountRepository->shouldNotReceive('save');
        /** @var PrincipalGroupRepositoryInterface&Mockery\MockInterface $principalGroupRepository */
        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $principalGroupRepository->shouldReceive('findByAccountIdAndPrincipal')->once()->andReturn([]);

        $this->expectException(AccountUpdateForbiddenException::class);

        (new UpdateAccount($accountRepository, $principalGroupRepository))->process($input, new UpdateAccountOutput());
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

    private function createPrincipalGroup(
        AccountIdentifier $accountIdentifier,
        AccountRole $role,
        IdentityIdentifier $member,
    ): PrincipalGroup {
        $principalGroup = new PrincipalGroup(
            new PrincipalGroupIdentifier(StrTestHelper::generateUuid()),
            $accountIdentifier,
            $role->value,
            $role,
            false,
            new DateTimeImmutable(),
        );
        $principalGroup->addMember($member);

        return $principalGroup;
    }
}
