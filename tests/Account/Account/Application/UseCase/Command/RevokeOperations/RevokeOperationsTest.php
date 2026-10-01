<?php

declare(strict_types=1);

namespace Tests\Account\Account\Application\UseCase\Command\RevokeOperations;

use DateTimeImmutable;
use Mockery;
use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Account\Application\UseCase\Command\RevokeOperations\RevokeOperations;
use Source\Account\Account\Application\UseCase\Command\RevokeOperations\RevokeOperationsInput;
use Source\Account\Account\Application\UseCase\Command\RevokeOperations\RevokeOperationsInterface;
use Source\Account\Account\Application\UseCase\Command\RevokeOperations\RevokeOperationsOutput;
use Source\Account\Account\Domain\Entity\Account;
use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\Account\Domain\ValueObject\AccountDocuments;
use Source\Account\Account\Domain\ValueObject\AccountName;
use Source\Account\Account\Domain\ValueObject\AccountStatus;
use Source\Account\Account\Domain\ValueObject\DeletionReadinessChecklist;
use Source\Account\Principal\Application\Exception\OperationsMembershipNotFoundException;
use Source\Account\Principal\Application\Exception\PrincipalGroupNotFoundException;
use Source\Account\Principal\Application\Exception\PrincipalNotFoundException;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Principal\Domain\Entity\PrincipalGroup;
use Source\Account\Principal\Domain\Entity\Role;
use Source\Account\Principal\Domain\Exception\SystemRoleNotFoundException;
use Source\Account\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Account\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Account\Principal\Domain\Repository\RoleRepositoryInterface;
use Source\Account\Principal\Domain\ValueObject\RoleIdentifier;
use Source\Account\Shared\Domain\ValueObject\AccountType;
use Source\Account\Shared\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Identity\Domain\Entity\Identity;
use Source\Identity\Domain\Exception\IdentityNotFoundException;
use Source\Identity\Domain\Repository\IdentityRepositoryInterface;
use Source\Identity\Domain\ValueObject\IdentityName;
use Source\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class RevokeOperationsTest extends TestCase
{
    public function test__construct(): void
    {
        $identityRepository = Mockery::mock(IdentityRepositoryInterface::class);
        $this->app()->instance(IdentityRepositoryInterface::class, $identityRepository);
        $accountRepository = Mockery::mock(AccountRepositoryInterface::class);
        $this->app()->instance(AccountRepositoryInterface::class, $accountRepository);
        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $this->app()->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $this->app()->instance(PrincipalGroupRepositoryInterface::class, $principalGroupRepository);
        $roleRepository = Mockery::mock(RoleRepositoryInterface::class);
        $this->app()->instance(RoleRepositoryInterface::class, $roleRepository);

        $this->assertInstanceOf(RevokeOperations::class, $this->app()->make(RevokeOperationsInterface::class));
    }

    public function testDeletesOperationsGroupWithSingleMember(): void
    {
        $identityRepository = Mockery::mock(IdentityRepositoryInterface::class);
        $this->app()->instance(IdentityRepositoryInterface::class, $identityRepository);
        $accountRepository = Mockery::mock(AccountRepositoryInterface::class);
        $this->app()->instance(AccountRepositoryInterface::class, $accountRepository);
        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $this->app()->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $this->app()->instance(PrincipalGroupRepositoryInterface::class, $principalGroupRepository);
        $roleRepository = Mockery::mock(RoleRepositoryInterface::class);
        $this->app()->instance(RoleRepositoryInterface::class, $roleRepository);

        $testData = $this->createTestData();
        $testData->principalGroup->addRole($testData->operationsRole);
        $testData->principalGroup->addMember($testData->principal->principalIdentifier());

        $identityRepository->shouldReceive('findByEmail')->once()
            ->with($testData->email)->andReturn($testData->identity);
        $accountRepository->shouldReceive('findByEmail')->once()
            ->with($testData->email)->andReturn($testData->account);
        $principalRepository->shouldReceive('findByIdentityIdentifierAndAccountIdentifier')->once()
            ->with($testData->identity->identityIdentifier(), $testData->account->accountIdentifier())->andReturn($testData->principal);
        $roleRepository->shouldReceive('findSystemByName')->once()
            ->with(Role::OPERATIONS)->andReturn($testData->operationsRole);
        $principalGroupRepository->shouldReceive('findByAccountIdAndRole')->once()
            ->with($testData->account->accountIdentifier(), $testData->operationsRole->roleIdentifier())->andReturn($testData->principalGroup);
        $principalGroupRepository->shouldReceive('delete')->once()->with($testData->principalGroup);
        $principalGroupRepository->shouldNotReceive('save');
        $accountRepository->shouldNotReceive('delete');
        $principalRepository->shouldNotReceive('save');

        $input = new RevokeOperationsInput($testData->email);
        $output = new RevokeOperationsOutput();
        $useCase = $this->app()->make(RevokeOperationsInterface::class);
        $useCase->process($input, $output);

        $this->assertTrue($testData->principalGroup->hasMember($testData->principal->principalIdentifier()));
        $this->assertTrue($testData->principalGroup->hasRole($testData->operationsRole->roleIdentifier()));
    }

    public function testThrowsIdentityNotFoundException(): void
    {
        $identityRepository = Mockery::mock(IdentityRepositoryInterface::class);
        $this->app()->instance(IdentityRepositoryInterface::class, $identityRepository);
        $accountRepository = Mockery::mock(AccountRepositoryInterface::class);
        $this->app()->instance(AccountRepositoryInterface::class, $accountRepository);
        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $this->app()->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $this->app()->instance(PrincipalGroupRepositoryInterface::class, $principalGroupRepository);
        $roleRepository = Mockery::mock(RoleRepositoryInterface::class);
        $this->app()->instance(RoleRepositoryInterface::class, $roleRepository);

        $testData = $this->createTestData();

        $identityRepository->shouldReceive('findByEmail')->once()
            ->with($testData->email)->andReturnNull();
        $principalGroupRepository->shouldNotReceive('save');
        $principalGroupRepository->shouldNotReceive('delete');
        $accountRepository->shouldNotReceive('findByEmail');

        $this->expectException(IdentityNotFoundException::class);
        $input = new RevokeOperationsInput($testData->email);
        $output = new RevokeOperationsOutput();
        $useCase = $this->app()->make(RevokeOperationsInterface::class);
        $useCase->process($input, $output);
    }

    public function testThrowsAccountNotFoundException(): void
    {
        $identityRepository = Mockery::mock(IdentityRepositoryInterface::class);
        $this->app()->instance(IdentityRepositoryInterface::class, $identityRepository);
        $accountRepository = Mockery::mock(AccountRepositoryInterface::class);
        $this->app()->instance(AccountRepositoryInterface::class, $accountRepository);
        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $this->app()->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $this->app()->instance(PrincipalGroupRepositoryInterface::class, $principalGroupRepository);
        $roleRepository = Mockery::mock(RoleRepositoryInterface::class);
        $this->app()->instance(RoleRepositoryInterface::class, $roleRepository);

        $testData = $this->createTestData();

        $identityRepository->shouldReceive('findByEmail')->once()
            ->with($testData->email)->andReturn($testData->identity);
        $accountRepository->shouldReceive('findByEmail')->once()
            ->with($testData->email)->andReturnNull();
        $principalGroupRepository->shouldNotReceive('save');
        $principalGroupRepository->shouldNotReceive('delete');
        $principalRepository->shouldNotReceive('findByIdentityIdentifierAndAccountIdentifier');

        $this->expectException(AccountNotFoundException::class);
        $input = new RevokeOperationsInput($testData->email);
        $output = new RevokeOperationsOutput();
        $useCase = $this->app()->make(RevokeOperationsInterface::class);
        $useCase->process($input, $output);
    }

    public function testThrowsPrincipalNotFoundException(): void
    {
        $identityRepository = Mockery::mock(IdentityRepositoryInterface::class);
        $this->app()->instance(IdentityRepositoryInterface::class, $identityRepository);
        $accountRepository = Mockery::mock(AccountRepositoryInterface::class);
        $this->app()->instance(AccountRepositoryInterface::class, $accountRepository);
        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $this->app()->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $this->app()->instance(PrincipalGroupRepositoryInterface::class, $principalGroupRepository);
        $roleRepository = Mockery::mock(RoleRepositoryInterface::class);
        $this->app()->instance(RoleRepositoryInterface::class, $roleRepository);

        $testData = $this->createTestData();

        $identityRepository->shouldReceive('findByEmail')->once()
            ->with($testData->email)->andReturn($testData->identity);
        $accountRepository->shouldReceive('findByEmail')->once()
            ->with($testData->email)->andReturn($testData->account);
        $principalRepository->shouldReceive('findByIdentityIdentifierAndAccountIdentifier')->once()
            ->with($testData->identity->identityIdentifier(), $testData->account->accountIdentifier())->andReturnNull();
        $principalGroupRepository->shouldNotReceive('save');
        $principalGroupRepository->shouldNotReceive('delete');
        $roleRepository->shouldNotReceive('findSystemByName');

        $this->expectException(PrincipalNotFoundException::class);
        $input = new RevokeOperationsInput($testData->email);
        $output = new RevokeOperationsOutput();
        $useCase = $this->app()->make(RevokeOperationsInterface::class);
        $useCase->process($input, $output);
    }

    public function testThrowsSystemRoleNotFoundException(): void
    {
        $identityRepository = Mockery::mock(IdentityRepositoryInterface::class);
        $this->app()->instance(IdentityRepositoryInterface::class, $identityRepository);
        $accountRepository = Mockery::mock(AccountRepositoryInterface::class);
        $this->app()->instance(AccountRepositoryInterface::class, $accountRepository);
        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $this->app()->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $this->app()->instance(PrincipalGroupRepositoryInterface::class, $principalGroupRepository);
        $roleRepository = Mockery::mock(RoleRepositoryInterface::class);
        $this->app()->instance(RoleRepositoryInterface::class, $roleRepository);

        $testData = $this->createTestData();

        $identityRepository->shouldReceive('findByEmail')->once()
            ->with($testData->email)->andReturn($testData->identity);
        $accountRepository->shouldReceive('findByEmail')->once()
            ->with($testData->email)->andReturn($testData->account);
        $principalRepository->shouldReceive('findByIdentityIdentifierAndAccountIdentifier')->once()
            ->with($testData->identity->identityIdentifier(), $testData->account->accountIdentifier())->andReturn($testData->principal);
        $roleRepository->shouldReceive('findSystemByName')->once()
            ->with(Role::OPERATIONS)->andReturnNull();
        $principalGroupRepository->shouldNotReceive('save');
        $principalGroupRepository->shouldNotReceive('delete');
        $principalGroupRepository->shouldNotReceive('findByAccountIdAndRole');

        $this->expectException(SystemRoleNotFoundException::class);
        $input = new RevokeOperationsInput($testData->email);
        $output = new RevokeOperationsOutput();
        $useCase = $this->app()->make(RevokeOperationsInterface::class);
        $useCase->process($input, $output);
    }

    public function testThrowsPrincipalGroupNotFoundException(): void
    {
        $identityRepository = Mockery::mock(IdentityRepositoryInterface::class);
        $this->app()->instance(IdentityRepositoryInterface::class, $identityRepository);
        $accountRepository = Mockery::mock(AccountRepositoryInterface::class);
        $this->app()->instance(AccountRepositoryInterface::class, $accountRepository);
        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $this->app()->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $this->app()->instance(PrincipalGroupRepositoryInterface::class, $principalGroupRepository);
        $roleRepository = Mockery::mock(RoleRepositoryInterface::class);
        $this->app()->instance(RoleRepositoryInterface::class, $roleRepository);

        $testData = $this->createTestData();

        $identityRepository->shouldReceive('findByEmail')->once()
            ->with($testData->email)->andReturn($testData->identity);
        $accountRepository->shouldReceive('findByEmail')->once()
            ->with($testData->email)->andReturn($testData->account);
        $principalRepository->shouldReceive('findByIdentityIdentifierAndAccountIdentifier')->once()
            ->with($testData->identity->identityIdentifier(), $testData->account->accountIdentifier())->andReturn($testData->principal);
        $roleRepository->shouldReceive('findSystemByName')->once()
            ->with(Role::OPERATIONS)->andReturn($testData->operationsRole);
        $principalGroupRepository->shouldReceive('findByAccountIdAndRole')->once()
            ->with($testData->account->accountIdentifier(), $testData->operationsRole->roleIdentifier())->andReturnNull();
        $principalGroupRepository->shouldNotReceive('save');
        $principalGroupRepository->shouldNotReceive('delete');

        $this->expectException(PrincipalGroupNotFoundException::class);
        $input = new RevokeOperationsInput($testData->email);
        $output = new RevokeOperationsOutput();
        $useCase = $this->app()->make(RevokeOperationsInterface::class);
        $useCase->process($input, $output);
    }

    public function testThrowsOperationsMembershipNotFoundException(): void
    {
        $identityRepository = Mockery::mock(IdentityRepositoryInterface::class);
        $this->app()->instance(IdentityRepositoryInterface::class, $identityRepository);
        $accountRepository = Mockery::mock(AccountRepositoryInterface::class);
        $this->app()->instance(AccountRepositoryInterface::class, $accountRepository);
        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $this->app()->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $this->app()->instance(PrincipalGroupRepositoryInterface::class, $principalGroupRepository);
        $roleRepository = Mockery::mock(RoleRepositoryInterface::class);
        $this->app()->instance(RoleRepositoryInterface::class, $roleRepository);

        $testData = $this->createTestData();
        $testData->principalGroup->addRole($testData->operationsRole);

        $identityRepository->shouldReceive('findByEmail')->once()
            ->with($testData->email)->andReturn($testData->identity);
        $accountRepository->shouldReceive('findByEmail')->once()
            ->with($testData->email)->andReturn($testData->account);
        $principalRepository->shouldReceive('findByIdentityIdentifierAndAccountIdentifier')->once()
            ->with($testData->identity->identityIdentifier(), $testData->account->accountIdentifier())->andReturn($testData->principal);
        $roleRepository->shouldReceive('findSystemByName')->once()
            ->with(Role::OPERATIONS)->andReturn($testData->operationsRole);
        $principalGroupRepository->shouldReceive('findByAccountIdAndRole')->once()
            ->with($testData->account->accountIdentifier(), $testData->operationsRole->roleIdentifier())->andReturn($testData->principalGroup);
        $principalGroupRepository->shouldNotReceive('delete');

        $this->expectException(OperationsMembershipNotFoundException::class);
        $input = new RevokeOperationsInput($testData->email);
        $output = new RevokeOperationsOutput();
        $useCase = $this->app()->make(RevokeOperationsInterface::class);
        $useCase->process($input, $output);
    }

    private function createTestData(): RevokeOperationsTestData
    {
        $email = new Email('operator@example.com');
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $identity = new Identity(
            $identityIdentifier,
            new IdentityName('Operator'),
            $email,
            Language::JAPANESE,
            null,
            new DateTimeImmutable(),
        );
        $account = new Account(
            $accountIdentifier,
            $email,
            AccountType::INDIVIDUAL,
            new AccountName('Existing Account'),
            AccountStatus::ACTIVE,
            AccountCategory::GENERAL,
            DeletionReadinessChecklist::ready(),
            new AccountDocuments(),
        );
        $principal = new Principal(new PrincipalIdentifier(StrTestHelper::generateUuid()), $identityIdentifier, $accountIdentifier);
        $operationsRole = new Role(new RoleIdentifier(StrTestHelper::generateUuid()), Role::OPERATIONS, [], null);
        $principalGroup = new PrincipalGroup(
            new PrincipalGroupIdentifier(StrTestHelper::generateUuid()),
            $accountIdentifier,
            'Operations',
            false,
            new DateTimeImmutable(),
        );

        return new RevokeOperationsTestData($email, $identity, $account, $principal, $operationsRole, $principalGroup);
    }
}

readonly class RevokeOperationsTestData
{
    public function __construct(
        public Email $email,
        public Identity $identity,
        public Account $account,
        public Principal $principal,
        public Role $operationsRole,
        public PrincipalGroup $principalGroup,
    ) {
    }
}
