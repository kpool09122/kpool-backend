<?php

declare(strict_types=1);

namespace Tests\Account\Account\Application\UseCase\Command\GrantOperations;

use DateTimeImmutable;
use Mockery;
use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Account\Application\UseCase\Command\GrantOperations\GrantOperations;
use Source\Account\Account\Application\UseCase\Command\GrantOperations\GrantOperationsInput;
use Source\Account\Account\Application\UseCase\Command\GrantOperations\GrantOperationsInterface;
use Source\Account\Account\Application\UseCase\Command\GrantOperations\GrantOperationsOutput;
use Source\Account\Account\Domain\Entity\Account;
use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\Account\Domain\ValueObject\AccountDocuments;
use Source\Account\Account\Domain\ValueObject\AccountName;
use Source\Account\Account\Domain\ValueObject\AccountStatus;
use Source\Account\Account\Domain\ValueObject\DeletionReadinessChecklist;
use Source\Account\Principal\Application\Exception\PrincipalNotFoundException;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Principal\Domain\Entity\PrincipalGroup;
use Source\Account\Principal\Domain\Entity\Role;
use Source\Account\Principal\Domain\Exception\SystemRoleNotFoundException;
use Source\Account\Principal\Domain\Factory\PrincipalGroupFactoryInterface;
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

class GrantOperationsTest extends TestCase
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
        $principalGroupFactory = Mockery::mock(PrincipalGroupFactoryInterface::class);
        $this->app()->instance(PrincipalGroupFactoryInterface::class, $principalGroupFactory);

        $this->assertInstanceOf(GrantOperations::class, $this->app()->make(GrantOperationsInterface::class));
    }

    public function testCreatesOperationsGroupWithOnlyMatchingPrincipal(): void
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
        $principalGroupFactory = Mockery::mock(PrincipalGroupFactoryInterface::class);
        $this->app()->instance(PrincipalGroupFactoryInterface::class, $principalGroupFactory);

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
        $principalGroupFactory->shouldReceive('create')->once()
            ->with($testData->account->accountIdentifier(), 'Operations', false)->andReturn($testData->principalGroup);
        $principalGroupRepository->shouldReceive('save')->once()->with($testData->principalGroup);
        $accountRepository->shouldNotReceive('save');
        $principalRepository->shouldNotReceive('save');

        $input = new GrantOperationsInput($testData->email);
        $output = new GrantOperationsOutput();
        $useCase = $this->app()->make(GrantOperationsInterface::class);
        $useCase->process($input, $output);

        $this->assertSame([$testData->operationsRole->roleIdentifier()], $testData->principalGroup->roles());
        $this->assertSame(
            [(string) $testData->principal->principalIdentifier() => $testData->principal->principalIdentifier()],
            $testData->principalGroup->members(),
        );
    }

    public function testAddsPrincipalToExistingOperationsGroup(): void
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
        $principalGroupFactory = Mockery::mock(PrincipalGroupFactoryInterface::class);
        $this->app()->instance(PrincipalGroupFactoryInterface::class, $principalGroupFactory);

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
        $principalGroupFactory->shouldNotReceive('create');
        $principalGroupRepository->shouldReceive('save')->once()->with($testData->principalGroup);
        $accountRepository->shouldNotReceive('save');
        $principalRepository->shouldNotReceive('save');

        $input = new GrantOperationsInput($testData->email);
        $output = new GrantOperationsOutput();
        $useCase = $this->app()->make(GrantOperationsInterface::class);
        $useCase->process($input, $output);

        $this->assertSame([$testData->operationsRole->roleIdentifier()], $testData->principalGroup->roles());
        $this->assertSame(
            [(string) $testData->principal->principalIdentifier() => $testData->principal->principalIdentifier()],
            $testData->principalGroup->members(),
        );
    }

    public function testDoesNotDuplicateExistingRoleOrMembership(): void
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
        $principalGroupFactory = Mockery::mock(PrincipalGroupFactoryInterface::class);
        $this->app()->instance(PrincipalGroupFactoryInterface::class, $principalGroupFactory);

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
        $principalGroupFactory->shouldNotReceive('create');
        $principalGroupRepository->shouldReceive('save')->once()->with($testData->principalGroup);
        $accountRepository->shouldNotReceive('save');
        $principalRepository->shouldNotReceive('save');

        $input = new GrantOperationsInput($testData->email);
        $output = new GrantOperationsOutput();
        $useCase = $this->app()->make(GrantOperationsInterface::class);
        $useCase->process($input, $output);

        $this->assertSame([$testData->operationsRole->roleIdentifier()], $testData->principalGroup->roles());
        $this->assertSame(
            [(string) $testData->principal->principalIdentifier() => $testData->principal->principalIdentifier()],
            $testData->principalGroup->members(),
        );
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
        $principalGroupFactory = Mockery::mock(PrincipalGroupFactoryInterface::class);
        $this->app()->instance(PrincipalGroupFactoryInterface::class, $principalGroupFactory);

        $testData = $this->createTestData();

        $identityRepository->shouldReceive('findByEmail')->once()
            ->with($testData->email)->andReturnNull();
        $principalGroupRepository->shouldNotReceive('save');
        $principalGroupRepository->shouldNotReceive('delete');
        $principalGroupFactory->shouldNotReceive('create');
        $accountRepository->shouldNotReceive('findByEmail');

        $this->expectException(IdentityNotFoundException::class);
        $input = new GrantOperationsInput($testData->email);
        $output = new GrantOperationsOutput();
        $useCase = $this->app()->make(GrantOperationsInterface::class);
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
        $principalGroupFactory = Mockery::mock(PrincipalGroupFactoryInterface::class);
        $this->app()->instance(PrincipalGroupFactoryInterface::class, $principalGroupFactory);

        $testData = $this->createTestData();

        $identityRepository->shouldReceive('findByEmail')->once()
            ->with($testData->email)->andReturn($testData->identity);
        $accountRepository->shouldReceive('findByEmail')->once()
            ->with($testData->email)->andReturnNull();
        $principalGroupRepository->shouldNotReceive('save');
        $principalGroupRepository->shouldNotReceive('delete');
        $principalGroupFactory->shouldNotReceive('create');
        $principalRepository->shouldNotReceive('findByIdentityIdentifierAndAccountIdentifier');

        $this->expectException(AccountNotFoundException::class);
        $input = new GrantOperationsInput($testData->email);
        $output = new GrantOperationsOutput();
        $useCase = $this->app()->make(GrantOperationsInterface::class);
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
        $principalGroupFactory = Mockery::mock(PrincipalGroupFactoryInterface::class);
        $this->app()->instance(PrincipalGroupFactoryInterface::class, $principalGroupFactory);

        $testData = $this->createTestData();

        $identityRepository->shouldReceive('findByEmail')->once()
            ->with($testData->email)->andReturn($testData->identity);
        $accountRepository->shouldReceive('findByEmail')->once()
            ->with($testData->email)->andReturn($testData->account);
        $principalRepository->shouldReceive('findByIdentityIdentifierAndAccountIdentifier')->once()
            ->with($testData->identity->identityIdentifier(), $testData->account->accountIdentifier())->andReturnNull();
        $principalGroupRepository->shouldNotReceive('save');
        $principalGroupRepository->shouldNotReceive('delete');
        $principalGroupFactory->shouldNotReceive('create');
        $roleRepository->shouldNotReceive('findSystemByName');

        $this->expectException(PrincipalNotFoundException::class);
        $input = new GrantOperationsInput($testData->email);
        $output = new GrantOperationsOutput();
        $useCase = $this->app()->make(GrantOperationsInterface::class);
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
        $principalGroupFactory = Mockery::mock(PrincipalGroupFactoryInterface::class);
        $this->app()->instance(PrincipalGroupFactoryInterface::class, $principalGroupFactory);

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
        $principalGroupFactory->shouldNotReceive('create');
        $principalGroupRepository->shouldNotReceive('findByAccountIdAndRole');

        $this->expectException(SystemRoleNotFoundException::class);
        $input = new GrantOperationsInput($testData->email);
        $output = new GrantOperationsOutput();
        $useCase = $this->app()->make(GrantOperationsInterface::class);
        $useCase->process($input, $output);
    }

    public function testGrantsOperationsWithoutEmailVerification(): void
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
        $principalGroupFactory = Mockery::mock(PrincipalGroupFactoryInterface::class);
        $this->app()->instance(PrincipalGroupFactoryInterface::class, $principalGroupFactory);

        $testData = $this->createTestData();
        $identity = new Identity(
            $testData->identity->identityIdentifier(),
            new IdentityName('Operator'),
            $testData->email,
            Language::JAPANESE,
            null,
            null,
        );
        $identityRepository->shouldReceive('findByEmail')->once()->with($testData->email)->andReturn($identity);
        $accountRepository->shouldReceive('findByEmail')->once()
            ->with($testData->email)->andReturn($testData->account);
        $principalRepository->shouldReceive('findByIdentityIdentifierAndAccountIdentifier')->once()
            ->with($identity->identityIdentifier(), $testData->account->accountIdentifier())->andReturn($testData->principal);
        $roleRepository->shouldReceive('findSystemByName')->once()
            ->with(Role::OPERATIONS)->andReturn($testData->operationsRole);
        $principalGroupRepository->shouldReceive('findByAccountIdAndRole')->once()
            ->with($testData->account->accountIdentifier(), $testData->operationsRole->roleIdentifier())->andReturnNull();
        $principalGroupFactory->shouldReceive('create')->once()
            ->with($testData->account->accountIdentifier(), 'Operations', false)->andReturn($testData->principalGroup);
        $principalGroupRepository->shouldReceive('save')->once()->with($testData->principalGroup);

        $input = new GrantOperationsInput($testData->email);
        $output = new GrantOperationsOutput();
        $useCase = $this->app()->make(GrantOperationsInterface::class);
        $useCase->process($input, $output);

        $this->assertNull($identity->emailVerifiedAt());
        $this->assertSame([$testData->operationsRole->roleIdentifier()], $testData->principalGroup->roles());
        $this->assertSame(
            [(string) $testData->principal->principalIdentifier() => $testData->principal->principalIdentifier()],
            $testData->principalGroup->members(),
        );
    }

    private function createTestData(): GrantOperationsTestData
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

        return new GrantOperationsTestData($email, $identity, $account, $principal, $operationsRole, $principalGroup);
    }
}

readonly class GrantOperationsTestData
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
