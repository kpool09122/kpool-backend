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
use Source\Account\Account\Domain\ValueObject\AccountName;
use Source\Account\Account\Domain\ValueObject\AccountStatus;
use Source\Account\Account\Domain\ValueObject\AccountType;
use Source\Account\Account\Domain\ValueObject\DeletionReadinessChecklist;
use Source\Account\IdentityGroup\Domain\Entity\IdentityGroup;
use Source\Account\IdentityGroup\Domain\Factory\IdentityGroupFactoryInterface;
use Source\Account\IdentityGroup\Domain\Repository\IdentityGroupRepositoryInterface;
use Source\Account\IdentityGroup\Domain\ValueObject\AccountRole;
use Source\Account\Shared\Domain\ValueObject\AccountCategory;
use Source\Account\Shared\Domain\ValueObject\IdentityGroupIdentifier;
use Source\Shared\Application\Service\Event\EventDispatcherInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
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
        $identityGroupFactory = Mockery::mock(IdentityGroupFactoryInterface::class);
        $identityGroupRepository = Mockery::mock(IdentityGroupRepositoryInterface::class);
        $eventDispatcher = Mockery::mock(EventDispatcherInterface::class);
        $this->app->instance(AccountRepositoryInterface::class, $repository);
        $this->app->instance(AccountFactoryInterface::class, $factory);
        $this->app->instance(IdentityGroupFactoryInterface::class, $identityGroupFactory);
        $this->app->instance(IdentityGroupRepositoryInterface::class, $identityGroupRepository);
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

        $identityGroupFactory = Mockery::mock(IdentityGroupFactoryInterface::class);
        $identityGroupFactory->shouldReceive('create')
            ->once()
            ->with($testData->identifier, 'Owners', AccountRole::OWNER, true)
            ->andReturn($testData->identityGroup);

        $identityGroupRepository = Mockery::mock(IdentityGroupRepositoryInterface::class);
        $identityGroupRepository->shouldReceive('save')
            ->once()
            ->with($testData->identityGroup)
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
        $this->app->instance(IdentityGroupFactoryInterface::class, $identityGroupFactory);
        $this->app->instance(IdentityGroupRepositoryInterface::class, $identityGroupRepository);
        $this->app->instance(EventDispatcherInterface::class, $eventDispatcher);

        $useCase = $this->app->make(CreateAccountInterface::class);

        $output = new CreateAccountOutput();
        $useCase->process($testData->input, $output);

        $result = $output->toArray();
        $this->assertSame((string) $testData->identifier, $result['accountIdentifier']);
        $this->assertSame((string) $testData->email, $result['email']);
        $this->assertSame($testData->accountType->value, $result['type']);
        $this->assertSame((string) $testData->accountName, $result['name']);
        $this->assertTrue($testData->identityGroup->hasMember($testData->identityIdentifier));
    }

    /**
     * 正常系: identityIdentifierがnullの場合もアカウントとデフォルトIdentityGroupが作成されること.
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

        $identityGroupFactory = Mockery::mock(IdentityGroupFactoryInterface::class);
        $identityGroupFactory->shouldReceive('create')
            ->once()
            ->with($testData->identifier, 'Owners', AccountRole::OWNER, true)
            ->andReturn($testData->identityGroup);

        $identityGroupRepository = Mockery::mock(IdentityGroupRepositoryInterface::class);
        $identityGroupRepository->shouldReceive('save')
            ->once()
            ->with($testData->identityGroup)
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
        $this->app->instance(IdentityGroupFactoryInterface::class, $identityGroupFactory);
        $this->app->instance(IdentityGroupRepositoryInterface::class, $identityGroupRepository);
        $this->app->instance(EventDispatcherInterface::class, $eventDispatcher);

        $useCase = $this->app->make(CreateAccountInterface::class);

        $output = new CreateAccountOutput();
        $useCase->process($testData->input, $output);

        $result = $output->toArray();
        $this->assertSame((string) $testData->identifier, $result['accountIdentifier']);
        $this->assertSame(0, $testData->identityGroup->memberCount());
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

        $identityGroupFactory = Mockery::mock(IdentityGroupFactoryInterface::class);
        $identityGroupFactory->shouldNotReceive('create');

        $identityGroupRepository = Mockery::mock(IdentityGroupRepositoryInterface::class);
        $identityGroupRepository->shouldNotReceive('save');

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
        $this->app->instance(IdentityGroupFactoryInterface::class, $identityGroupFactory);
        $this->app->instance(IdentityGroupRepositoryInterface::class, $identityGroupRepository);
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
        );

        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());

        $identityGroup = new IdentityGroup(
            new IdentityGroupIdentifier(StrTestHelper::generateUuid()),
            $identifier,
            'Owners',
            AccountRole::OWNER,
            true,
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
            $identityGroup,
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
        public IdentityGroup $identityGroup,
        public Language $language,
    ) {
    }
}
