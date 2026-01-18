<?php

declare(strict_types=1);

namespace Tests\Account\Account\Application\UseCase\Command\CreateAccount;

use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Account\Account\Application\Exception\AccountAlreadyExistsException;
use Source\Account\Account\Application\UseCase\Command\CreateAccount\CreateAccount;
use Source\Account\Account\Application\UseCase\Command\CreateAccount\CreateAccountInput;
use Source\Account\Account\Application\UseCase\Command\CreateAccount\CreateAccountInterface;
use Source\Account\Account\Domain\Entity\Account;
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
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
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
        $this->app->instance(AccountRepositoryInterface::class, $repository);
        $this->app->instance(AccountFactoryInterface::class, $factory);
        $this->app->instance(IdentityGroupFactoryInterface::class, $identityGroupFactory);
        $this->app->instance(IdentityGroupRepositoryInterface::class, $identityGroupRepository);
        $useCase = $this->app->make(CreateAccountInterface::class);
        $this->assertInstanceOf(CreateAccount::class, $useCase);
    }

    /**
     * 正常系: 正しくアカウントを作成できること（identityIdentifierあり）.
     *
     * @throws AccountAlreadyExistsException
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

        $this->app->instance(AccountRepositoryInterface::class, $repository);
        $this->app->instance(AccountFactoryInterface::class, $factory);
        $this->app->instance(IdentityGroupFactoryInterface::class, $identityGroupFactory);
        $this->app->instance(IdentityGroupRepositoryInterface::class, $identityGroupRepository);

        $useCase = $this->app->make(CreateAccountInterface::class);

        $account = $useCase->process($testData->input);

        $this->assertSame((string) $testData->identifier, (string) $account->accountIdentifier());
        $this->assertSame((string) $testData->email, (string) $account->email());
        $this->assertSame($testData->accountType, $account->type());
        $this->assertSame((string) $testData->accountName, (string) $account->name());
        $this->assertTrue($testData->identityGroup->hasMember($testData->identityIdentifier));
    }

    /**
     * 正常系: identityIdentifierがnullの場合もアカウントとデフォルトIdentityGroupが作成されること.
     *
     * @throws AccountAlreadyExistsException
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

        $this->app->instance(AccountRepositoryInterface::class, $repository);
        $this->app->instance(AccountFactoryInterface::class, $factory);
        $this->app->instance(IdentityGroupFactoryInterface::class, $identityGroupFactory);
        $this->app->instance(IdentityGroupRepositoryInterface::class, $identityGroupRepository);

        $useCase = $this->app->make(CreateAccountInterface::class);

        $account = $useCase->process($testData->input);

        $this->assertSame((string) $testData->identifier, (string) $account->accountIdentifier());
        $this->assertSame(0, $testData->identityGroup->memberCount());
    }

    /**
     * 異常系: アカウントが重複した時に、例外がスローされること.
     *
     * @throws AccountAlreadyExistsException
     * @throws BindingResolutionException
     */
    public function testThrowsWhenDuplicate(): void
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

        $this->app->instance(AccountRepositoryInterface::class, $repository);
        $this->app->instance(AccountFactoryInterface::class, $factory);
        $this->app->instance(IdentityGroupFactoryInterface::class, $identityGroupFactory);
        $this->app->instance(IdentityGroupRepositoryInterface::class, $identityGroupRepository);

        $useCase = $this->app->make(CreateAccountInterface::class);

        $this->expectException(AccountAlreadyExistsException::class);

        $useCase->process($input);
    }

    private function createDummyAccountTestData(bool $includeIdentityIdentifier = true): CreateAccountTestData
    {
        $identifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $email = new Email('test@test.com');
        $accountType = AccountType::CORPORATION;
        $accountName = new AccountName('Example Inc');

        $status = AccountStatus::ACTIVE;
        $accountCategory = AccountCategory::GENERAL;

        $account = new Account(
            $identifier,
            $email,
            $accountType,
            $accountName,
            null,
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
    ) {
    }
}
