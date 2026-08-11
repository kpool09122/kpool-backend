<?php

declare(strict_types=1);

namespace Tests\Account\Account\Application\UseCase\Command\CreateAccount;

use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Account\Account\Application\UseCase\Command\CreateAccount\CreateAccount;
use Source\Account\Account\Application\UseCase\Command\CreateAccount\CreateAccountInput;
use Source\Account\Account\Application\UseCase\Command\CreateAccount\CreateAccountInterface;
use Source\Account\Account\Application\UseCase\Command\CreateAccount\CreateAccountOutput;
use Source\Account\Account\Domain\Entity\Account;
use Source\Account\Account\Domain\Event\AccountCreated;
use Source\Account\Account\Domain\Event\AccountCreationConflicted;
use Source\Account\Account\Domain\Factory\AccountFactoryInterface;
use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\Account\Domain\ValueObject\AccountDocuments;
use Source\Account\Account\Domain\ValueObject\AccountName;
use Source\Account\Account\Domain\ValueObject\AccountStatus;
use Source\Account\Account\Domain\ValueObject\AccountType;
use Source\Account\Account\Domain\ValueObject\DeletionReadinessChecklist;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Principal\Domain\Entity\PrincipalGroup;
use Source\Account\Principal\Domain\Entity\Role;
use Source\Account\Principal\Domain\Factory\PrincipalFactoryInterface;
use Source\Account\Principal\Domain\Factory\PrincipalGroupFactoryInterface;
use Source\Account\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Account\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Account\Principal\Domain\Repository\RoleRepositoryInterface;
use Source\Account\Principal\Domain\ValueObject\RoleIdentifier;
use Source\Account\Shared\Domain\ValueObject\AccountCategory;
use Source\Account\Shared\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Application\Service\Event\EventDispatcherInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\Phone;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class CreateAccountTest extends TestCase
{
    /**
     * 正常系: 正しくDIが動作すること
     *
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $repository = Mockery::mock(AccountRepositoryInterface::class);
        $factory = Mockery::mock(AccountFactoryInterface::class);
        $principalFactory = Mockery::mock(PrincipalFactoryInterface::class);
        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalGroupFactory = Mockery::mock(PrincipalGroupFactoryInterface::class);
        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $roleRepository = Mockery::mock(RoleRepositoryInterface::class);
        $eventDispatcher = Mockery::mock(EventDispatcherInterface::class);
        $this->app->instance(AccountRepositoryInterface::class, $repository);
        $this->app->instance(AccountFactoryInterface::class, $factory);
        $this->app->instance(PrincipalFactoryInterface::class, $principalFactory);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(PrincipalGroupFactoryInterface::class, $principalGroupFactory);
        $this->app->instance(PrincipalGroupRepositoryInterface::class, $principalGroupRepository);
        $this->app->instance(RoleRepositoryInterface::class, $roleRepository);
        $this->app->instance(EventDispatcherInterface::class, $eventDispatcher);
        $useCase = $this->app->make(CreateAccountInterface::class);
        $this->assertInstanceOf(CreateAccount::class, $useCase);
    }

    /**
     * 正常系: 正しくアカウントを作成できること（identityIdentifierあり）.
     *
     * @throws BindingResolutionException
     */
    public function testProcess(): void
    {
        $testData = $this->createDummyAccountTestData();

        $repository = Mockery::mock(AccountRepositoryInterface::class);
        $repository->shouldReceive('findByEmail')
            ->with($testData->email)
            ->once()
            ->andReturnNull();
        $repository->shouldReceive('save')
            ->once()
            ->with($testData->account)
            ->andReturnNull();

        $factory = Mockery::mock(AccountFactoryInterface::class);
        $factory->shouldReceive('create')
            ->once()
            ->with($testData->email, $testData->accountType, $testData->accountName)
            ->andReturn($testData->account);

        $principalFactory = Mockery::mock(PrincipalFactoryInterface::class);
        $principalFactory->shouldReceive('create')
            ->once()
            ->with($testData->identityIdentifier, $testData->identifier)
            ->andReturn($testData->principal);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('save')
            ->once()
            ->with($testData->principal);

        $principalGroupFactory = Mockery::mock(PrincipalGroupFactoryInterface::class);
        $principalGroupFactory->shouldReceive('create')
            ->once()
            ->with($testData->identifier, 'Default', true)
            ->andReturn($testData->defaultPrincipalGroup);
        $principalGroupFactory->shouldReceive('create')
            ->once()
            ->with($testData->identifier, 'Owners', false)
            ->andReturn($testData->ownerPrincipalGroup);

        $roleRepository = Mockery::mock(RoleRepositoryInterface::class);
        $roleRepository->shouldReceive('findByName')
            ->once()
            ->with(Role::OWNER)
            ->andReturn($testData->ownerRole);

        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $principalGroupRepository->shouldReceive('save')
            ->once()
            ->with($testData->defaultPrincipalGroup)
            ->andReturnNull();
        $principalGroupRepository->shouldReceive('save')
            ->once()
            ->with($testData->ownerPrincipalGroup)
            ->andReturnNull();

        $eventDispatcher = Mockery::mock(EventDispatcherInterface::class);
        $eventDispatcher->shouldReceive('dispatch')
            ->once()
            ->with(Mockery::on(
                static fn (object $event): bool => $event instanceof AccountCreated
                    && (string) $event->accountIdentifier === (string) $testData->identifier
                    && (string) $event->email === (string) $testData->email
                    && (string) $event->identityIdentifier === (string) $testData->identityIdentifier
                    && $event->language === $testData->language
            ));

        $this->app->instance(AccountRepositoryInterface::class, $repository);
        $this->app->instance(AccountFactoryInterface::class, $factory);
        $this->app->instance(PrincipalFactoryInterface::class, $principalFactory);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(PrincipalGroupFactoryInterface::class, $principalGroupFactory);
        $this->app->instance(PrincipalGroupRepositoryInterface::class, $principalGroupRepository);
        $this->app->instance(RoleRepositoryInterface::class, $roleRepository);
        $this->app->instance(EventDispatcherInterface::class, $eventDispatcher);

        $useCase = $this->app->make(CreateAccountInterface::class);

        $output = new CreateAccountOutput();
        $useCase->process($testData->input, $output);

        $result = $output->toArray();
        $this->assertSame((string) $testData->identifier, $result['accountIdentifier']);
        $this->assertSame((string) $testData->email, $result['email']);
        $this->assertSame($testData->accountType->value, $result['type']);
        $this->assertSame((string) $testData->accountName, $result['name']);
        $this->assertSame(0, $testData->defaultPrincipalGroup->memberCount());
        $this->assertTrue($testData->ownerPrincipalGroup->hasMember($testData->principalIdentifier));
        $this->assertTrue($testData->ownerPrincipalGroup->hasRole($testData->ownerRole->roleIdentifier()));
    }

    /**
     * 正常系: identityIdentifierがnullの場合もアカウントとデフォルトPrincipalGroupが作成されること.
     *
     * @throws BindingResolutionException
     */
    public function testProcessWithoutIdentityIdentifier(): void
    {
        $testData = $this->createDummyAccountTestData(includeIdentityIdentifier: false);

        $repository = Mockery::mock(AccountRepositoryInterface::class);
        $repository->shouldReceive('findByEmail')
            ->with($testData->email)
            ->once()
            ->andReturnNull();
        $repository->shouldReceive('save')
            ->once()
            ->with($testData->account)
            ->andReturnNull();

        $factory = Mockery::mock(AccountFactoryInterface::class);
        $factory->shouldReceive('create')
            ->once()
            ->with($testData->email, $testData->accountType, $testData->accountName)
            ->andReturn($testData->account);

        $principalFactory = Mockery::mock(PrincipalFactoryInterface::class);
        $principalFactory->shouldNotReceive('create');

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldNotReceive('save');

        $principalGroupFactory = Mockery::mock(PrincipalGroupFactoryInterface::class);
        $principalGroupFactory->shouldReceive('create')
            ->once()
            ->with($testData->identifier, 'Default', true)
            ->andReturn($testData->defaultPrincipalGroup);
        $principalGroupFactory->shouldReceive('create')
            ->once()
            ->with($testData->identifier, 'Owners', false)
            ->andReturn($testData->ownerPrincipalGroup);

        $roleRepository = Mockery::mock(RoleRepositoryInterface::class);
        $roleRepository->shouldReceive('findByName')
            ->once()
            ->with(Role::OWNER)
            ->andReturn($testData->ownerRole);

        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $principalGroupRepository->shouldReceive('save')
            ->once()
            ->with($testData->defaultPrincipalGroup)
            ->andReturnNull();
        $principalGroupRepository->shouldReceive('save')
            ->once()
            ->with($testData->ownerPrincipalGroup)
            ->andReturnNull();

        $eventDispatcher = Mockery::mock(EventDispatcherInterface::class);
        $eventDispatcher->shouldReceive('dispatch')
            ->once()
            ->with(Mockery::on(
                static fn (object $event): bool => $event instanceof AccountCreated
                    && (string) $event->accountIdentifier === (string) $testData->identifier
                    && (string) $event->email === (string) $testData->email
                    && $event->identityIdentifier === null
                    && $event->language === $testData->language
            ));

        $this->app->instance(AccountRepositoryInterface::class, $repository);
        $this->app->instance(AccountFactoryInterface::class, $factory);
        $this->app->instance(PrincipalFactoryInterface::class, $principalFactory);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(PrincipalGroupFactoryInterface::class, $principalGroupFactory);
        $this->app->instance(PrincipalGroupRepositoryInterface::class, $principalGroupRepository);
        $this->app->instance(RoleRepositoryInterface::class, $roleRepository);
        $this->app->instance(EventDispatcherInterface::class, $eventDispatcher);

        $useCase = $this->app->make(CreateAccountInterface::class);

        $output = new CreateAccountOutput();
        $useCase->process($testData->input, $output);

        $result = $output->toArray();
        $this->assertSame((string) $testData->identifier, $result['accountIdentifier']);
        $this->assertSame(0, $testData->defaultPrincipalGroup->memberCount());
        $this->assertSame(0, $testData->ownerPrincipalGroup->memberCount());
    }

    /**
     * @throws BindingResolutionException
     */
    public function testProcessAppliesContactInformationAfterAccountCreation(): void
    {
        $testData = $this->createDummyAccountTestData();
        $input = new CreateAccountInput(
            email: $testData->email,
            accountType: $testData->accountType,
            accountName: $testData->accountName,
            identityIdentifier: $testData->identityIdentifier,
            language: $testData->language,
            phone: new Phone('+81 90 1234 5678'),
            addressCountryCode: 'JP',
            addressAdministrativeAreaCode: '13',
            addressPostalCode: '100-0001',
            addressLocality: '千代田区',
            addressLine1: '千代田1-1',
            addressLine2: null,
        );

        $repository = Mockery::mock(AccountRepositoryInterface::class);
        $repository->shouldReceive('findByEmail')
            ->with($testData->email)
            ->once()
            ->andReturnNull();
        $repository->shouldReceive('save')
            ->once()
            ->with(Mockery::on(static fn (Account $account): bool => (string) $account->phone() === '+819012345678'
                && $account->address()?->toArray() === [
                    'countryCode' => 'JP',
                    'administrativeAreaCode' => '13',
                    'postalCode' => '100-0001',
                    'locality' => '千代田区',
                    'addressLine1' => '千代田1-1',
                    'addressLine2' => null,
                ]))
            ->andReturnNull();

        $factory = Mockery::mock(AccountFactoryInterface::class);
        $factory->shouldReceive('create')
            ->once()
            ->with($testData->email, $testData->accountType, $testData->accountName)
            ->andReturn($testData->account);

        $principalFactory = Mockery::mock(PrincipalFactoryInterface::class);
        $principalFactory->shouldReceive('create')
            ->once()
            ->andReturn($testData->principal);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('save')->once();

        $principalGroupFactory = Mockery::mock(PrincipalGroupFactoryInterface::class);
        $principalGroupFactory->shouldReceive('create')
            ->once()
            ->with($testData->identifier, 'Default', true)
            ->andReturn($testData->defaultPrincipalGroup);
        $principalGroupFactory->shouldReceive('create')
            ->once()
            ->with($testData->identifier, 'Owners', false)
            ->andReturn($testData->ownerPrincipalGroup);

        $roleRepository = Mockery::mock(RoleRepositoryInterface::class);
        $roleRepository->shouldReceive('findByName')
            ->once()
            ->with(Role::OWNER)
            ->andReturn($testData->ownerRole);

        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $principalGroupRepository->shouldReceive('save')->twice();

        $eventDispatcher = Mockery::mock(EventDispatcherInterface::class);
        $eventDispatcher->shouldReceive('dispatch')->once();

        $this->app->instance(AccountRepositoryInterface::class, $repository);
        $this->app->instance(AccountFactoryInterface::class, $factory);
        $this->app->instance(PrincipalFactoryInterface::class, $principalFactory);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(PrincipalGroupFactoryInterface::class, $principalGroupFactory);
        $this->app->instance(PrincipalGroupRepositoryInterface::class, $principalGroupRepository);
        $this->app->instance(RoleRepositoryInterface::class, $roleRepository);
        $this->app->instance(EventDispatcherInterface::class, $eventDispatcher);

        $useCase = $this->app->make(CreateAccountInterface::class);
        $output = new CreateAccountOutput();
        $useCase->process($input, $output);

        $this->assertSame('+819012345678', $output->toArray()['phone']);
    }

    /**
     * 正常系: アカウントが重複した時に、通知イベントを発火して早期returnすること.
     *
     * @throws BindingResolutionException
     */
    public function testProcessDispatchesConflictEventWhenDuplicate(): void
    {
        $testData = $this->createDummyAccountTestData();
        $input = $testData->input;

        $repository = Mockery::mock(AccountRepositoryInterface::class);
        $repository->shouldReceive('findByEmail')
            ->once()
            ->with($testData->email)
            ->andReturn($testData->account);
        $repository->shouldNotReceive('save');

        $factory = Mockery::mock(AccountFactoryInterface::class);
        $factory->shouldNotReceive('create');

        $principalFactory = Mockery::mock(PrincipalFactoryInterface::class);
        $principalFactory->shouldNotReceive('create');

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldNotReceive('save');

        $principalGroupFactory = Mockery::mock(PrincipalGroupFactoryInterface::class);
        $principalGroupFactory->shouldNotReceive('create');

        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $principalGroupRepository->shouldNotReceive('save');
        $roleRepository = Mockery::mock(RoleRepositoryInterface::class);
        $roleRepository->shouldNotReceive('findByName');

        $eventDispatcher = Mockery::mock(EventDispatcherInterface::class);
        $eventDispatcher->shouldReceive('dispatch')
            ->once()
            ->with(Mockery::on(
                static fn (object $event): bool => $event instanceof AccountCreationConflicted
                    && (string) $event->email === (string) $testData->email
                    && $event->language === $testData->language
            ));

        $this->app->instance(AccountRepositoryInterface::class, $repository);
        $this->app->instance(AccountFactoryInterface::class, $factory);
        $this->app->instance(PrincipalFactoryInterface::class, $principalFactory);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(PrincipalGroupFactoryInterface::class, $principalGroupFactory);
        $this->app->instance(PrincipalGroupRepositoryInterface::class, $principalGroupRepository);
        $this->app->instance(RoleRepositoryInterface::class, $roleRepository);
        $this->app->instance(EventDispatcherInterface::class, $eventDispatcher);

        $useCase = $this->app->make(CreateAccountInterface::class);

        $output = new CreateAccountOutput();
        $useCase->process($input, $output);

        $this->assertSame([], $output->toArray());
    }

    private function createDummyAccountTestData(bool $includeIdentityIdentifier = true): CreateAccountTestData
    {
        $identifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $email = new Email('test@test.com');
        $accountType = AccountType::CORPORATION;
        $accountName = new AccountName('Example Inc');
        $language = Language::JAPANESE;

        $status = AccountStatus::ACTIVE;
        $accountCategory = AccountCategory::GENERAL;

        $account = new Account(
            $identifier,
            $email,
            $accountType,
            $accountName,
            $status,
            $accountCategory,
            DeletionReadinessChecklist::ready(),
            new AccountDocuments(),
        );

        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, $identityIdentifier, $identifier);

        $ownerRole = new Role(
            new RoleIdentifier(StrTestHelper::generateUuid()),
            Role::OWNER,
            [],
            true,
        );

        $defaultPrincipalGroup = new PrincipalGroup(
            new PrincipalGroupIdentifier(StrTestHelper::generateUuid()),
            $identifier,
            'Default',
            true,
            new \DateTimeImmutable(),
        );

        $ownerPrincipalGroup = new PrincipalGroup(
            new PrincipalGroupIdentifier(StrTestHelper::generateUuid()),
            $identifier,
            'Owners',
            false,
            new \DateTimeImmutable(),
        );

        $input = new CreateAccountInput(
            $email,
            $accountType,
            $accountName,
            $includeIdentityIdentifier ? $identityIdentifier : null,
            $language,
        );

        return new CreateAccountTestData(
            $identifier,
            $email,
            $accountType,
            $accountName,
            $accountCategory,
            $account,
            $input,
            $identityIdentifier,
            $principalIdentifier,
            $principal,
            $defaultPrincipalGroup,
            $ownerPrincipalGroup,
            $ownerRole,
            $language,
        );
    }
}

readonly class CreateAccountTestData
{
    public function __construct(
        public AccountIdentifier $identifier,
        public Email $email,
        public AccountType $accountType,
        public AccountName $accountName,
        public AccountCategory $accountCategory,
        public Account $account,
        public CreateAccountInput $input,
        public IdentityIdentifier $identityIdentifier,
        public PrincipalIdentifier $principalIdentifier,
        public Principal $principal,
        public PrincipalGroup $defaultPrincipalGroup,
        public PrincipalGroup $ownerPrincipalGroup,
        public Role $ownerRole,
        public Language $language,
    ) {
    }
}
