<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Application\UseCase\Command\CreatePrincipal;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Account\Account\Domain\Entity\Account;
use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\Account\Domain\ValueObject\AccountName;
use Source\Account\Account\Domain\ValueObject\AccountStatus;
use Source\Account\Account\Domain\ValueObject\AccountType;
use Source\Account\Account\Domain\ValueObject\AddressLine;
use Source\Account\Account\Domain\ValueObject\BillingAddress;
use Source\Account\Account\Domain\ValueObject\BillingContact;
use Source\Account\Account\Domain\ValueObject\BillingCycle;
use Source\Account\Account\Domain\ValueObject\BillingMethod;
use Source\Account\Account\Domain\ValueObject\City;
use Source\Account\Account\Domain\ValueObject\ContractInfo;
use Source\Account\Account\Domain\ValueObject\ContractName;
use Source\Account\Account\Domain\ValueObject\DeletionReadinessChecklist;
use Source\Account\Account\Domain\ValueObject\Plan;
use Source\Account\Account\Domain\ValueObject\PlanDescription;
use Source\Account\Account\Domain\ValueObject\PlanName;
use Source\Account\Account\Domain\ValueObject\PostalCode;
use Source\Account\Account\Domain\ValueObject\StateOrProvince;
use Source\Account\Account\Domain\ValueObject\TaxCategory;
use Source\Account\Account\Domain\ValueObject\TaxInfo;
use Source\Account\Account\Domain\ValueObject\TaxRegion;
use Source\Account\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\CountryCode;
use Source\Shared\Domain\ValueObject\Currency;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Money;
use Source\Wiki\Principal\Application\UseCase\Command\CreatePrincipal\CreatePrincipalInput;
use Source\Wiki\Principal\Application\UseCase\Command\CreatePrincipal\CreatePrincipalInterface;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Entity\PrincipalGroup;
use Source\Wiki\Principal\Domain\Entity\Role;
use Source\Wiki\Principal\Domain\Exception\PrincipalAlreadyExistsException;
use Source\Wiki\Principal\Domain\Factory\PrincipalFactoryInterface;
use Source\Wiki\Principal\Domain\Factory\PrincipalGroupFactoryInterface;
use Source\Wiki\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Repository\RoleRepositoryInterface;
use Source\Wiki\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\RoleIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class CreatePrincipalTest extends TestCase
{
    /**
     * 正常系: 正しくプリンシパルを作成できること（Default PrincipalGroup が存在しない場合）.
     *
     * @return void
     * @throws PrincipalAlreadyExistsException
     * @throws BindingResolutionException
     */
    public function testProcess(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principalGroupIdentifier = new PrincipalGroupIdentifier(StrTestHelper::generateUuid());

        $input = new CreatePrincipalInput(
            $identityIdentifier,
            $accountIdentifier,
        );

        $expectedPrincipal = new Principal(
            $principalIdentifier,
            $identityIdentifier,
            null,
            [],
            [],
        );

        $defaultPrincipalGroup = new PrincipalGroup(
            $principalGroupIdentifier,
            $accountIdentifier,
            'Default',
            true,
            new DateTimeImmutable(),
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findByIdentityIdentifier')
            ->with($identityIdentifier)
            ->once()
            ->andReturn(null);

        $principalFactory = Mockery::mock(PrincipalFactoryInterface::class);
        $principalFactory->shouldReceive('create')
            ->with($identityIdentifier)
            ->once()
            ->andReturn($expectedPrincipal);

        $principalRepository->shouldReceive('save')
            ->with($expectedPrincipal)
            ->once();

        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $principalGroupRepository->shouldReceive('findDefaultByAccountId')
            ->with($accountIdentifier)
            ->once()
            ->andReturn(null);

        $principalGroupFactory = Mockery::mock(PrincipalGroupFactoryInterface::class);
        $principalGroupFactory->shouldReceive('create')
            ->with($accountIdentifier, 'Default', true)
            ->once()
            ->andReturn($defaultPrincipalGroup);

        $principalGroupRepository->shouldReceive('save')
            ->with($defaultPrincipalGroup)
            ->twice();

        $accountRepository = Mockery::mock(AccountRepositoryInterface::class);
        $accountRepository->shouldReceive('findById')
            ->with($accountIdentifier)
            ->once()
            ->andReturnNull();

        $roleRepository = Mockery::mock(RoleRepositoryInterface::class);
        $roleRepository->shouldReceive('findByName')
            ->with('COLLABORATOR')
            ->once()
            ->andReturnNull();

        $this->app->instance(PrincipalFactoryInterface::class, $principalFactory);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(PrincipalGroupFactoryInterface::class, $principalGroupFactory);
        $this->app->instance(PrincipalGroupRepositoryInterface::class, $principalGroupRepository);
        $this->app->instance(AccountRepositoryInterface::class, $accountRepository);
        $this->app->instance(RoleRepositoryInterface::class, $roleRepository);
        $useCase = $this->app->make(CreatePrincipalInterface::class);
        $result = $useCase->process($input);

        $this->assertSame((string) $principalIdentifier, (string) $result->principalIdentifier());
        $this->assertSame((string) $identityIdentifier, (string) $result->identityIdentifier());
        $this->assertTrue($defaultPrincipalGroup->hasMember($principalIdentifier));
    }

    /**
     * 正常系: Default PrincipalGroup が既に存在する場合、既存のグループに Principal を追加すること.
     *
     * @return void
     * @throws PrincipalAlreadyExistsException
     * @throws BindingResolutionException
     */
    public function testProcessWithExistingDefaultPrincipalGroup(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principalGroupIdentifier = new PrincipalGroupIdentifier(StrTestHelper::generateUuid());

        $input = new CreatePrincipalInput(
            $identityIdentifier,
            $accountIdentifier,
        );

        $expectedPrincipal = new Principal(
            $principalIdentifier,
            $identityIdentifier,
            null,
            [],
            [],
        );

        $existingDefaultPrincipalGroup = new PrincipalGroup(
            $principalGroupIdentifier,
            $accountIdentifier,
            'Default',
            true,
            new DateTimeImmutable(),
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findByIdentityIdentifier')
            ->with($identityIdentifier)
            ->once()
            ->andReturn(null);

        $principalFactory = Mockery::mock(PrincipalFactoryInterface::class);
        $principalFactory->shouldReceive('create')
            ->with($identityIdentifier)
            ->once()
            ->andReturn($expectedPrincipal);

        $principalRepository->shouldReceive('save')
            ->with($expectedPrincipal)
            ->once();

        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $principalGroupRepository->shouldReceive('findDefaultByAccountId')
            ->with($accountIdentifier)
            ->once()
            ->andReturn($existingDefaultPrincipalGroup);

        $principalGroupFactory = Mockery::mock(PrincipalGroupFactoryInterface::class);
        $principalGroupFactory->shouldNotReceive('create');

        $principalGroupRepository->shouldReceive('save')
            ->with($existingDefaultPrincipalGroup)
            ->once();

        $accountRepository = Mockery::mock(AccountRepositoryInterface::class);
        $accountRepository->shouldNotReceive('findById');

        $roleRepository = Mockery::mock(RoleRepositoryInterface::class);
        $roleRepository->shouldNotReceive('findByName');

        $this->app->instance(PrincipalFactoryInterface::class, $principalFactory);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(PrincipalGroupFactoryInterface::class, $principalGroupFactory);
        $this->app->instance(PrincipalGroupRepositoryInterface::class, $principalGroupRepository);
        $this->app->instance(AccountRepositoryInterface::class, $accountRepository);
        $this->app->instance(RoleRepositoryInterface::class, $roleRepository);
        $useCase = $this->app->make(CreatePrincipalInterface::class);
        $result = $useCase->process($input);

        $this->assertSame((string) $principalIdentifier, (string) $result->principalIdentifier());
        $this->assertSame((string) $identityIdentifier, (string) $result->identityIdentifier());
        $this->assertTrue($existingDefaultPrincipalGroup->hasMember($principalIdentifier));
    }

    /**
     * 異常系: すでに生成済みのプリンシパルを作成しようとした場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testProcessThrowsExceptionWhenPrincipalAlreadyExists(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $input = new CreatePrincipalInput(
            $identityIdentifier,
            $accountIdentifier,
        );

        $existingPrincipal = new Principal(
            $principalIdentifier,
            $identityIdentifier,
            null,
            [],
            [],
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findByIdentityIdentifier')
            ->with($identityIdentifier)
            ->once()
            ->andReturn($existingPrincipal);
        $principalRepository->shouldNotReceive('save');

        $principalFactory = Mockery::mock(PrincipalFactoryInterface::class);
        $principalFactory->shouldNotReceive('create');

        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $principalGroupRepository->shouldNotReceive('findDefaultByAccountId');
        $principalGroupRepository->shouldNotReceive('save');

        $principalGroupFactory = Mockery::mock(PrincipalGroupFactoryInterface::class);
        $principalGroupFactory->shouldNotReceive('create');

        $accountRepository = Mockery::mock(AccountRepositoryInterface::class);
        $accountRepository->shouldNotReceive('findById');

        $roleRepository = Mockery::mock(RoleRepositoryInterface::class);
        $roleRepository->shouldNotReceive('findByName');

        $this->expectException(PrincipalAlreadyExistsException::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(PrincipalGroupRepositoryInterface::class, $principalGroupRepository);
        $this->app->instance(PrincipalGroupFactoryInterface::class, $principalGroupFactory);
        $this->app->instance(AccountRepositoryInterface::class, $accountRepository);
        $this->app->instance(RoleRepositoryInterface::class, $roleRepository);
        $useCase = $this->app->make(CreatePrincipalInterface::class);
        $useCase->process($input);
    }

    /**
     * 正常系: AccountがAGENCYの場合、AGENCY_ACTOR_ROLEが付与されること.
     *
     * @return void
     * @throws PrincipalAlreadyExistsException
     * @throws BindingResolutionException
     */
    public function testProcessWithAgencyAccountAssignsAgencyActorRole(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principalGroupIdentifier = new PrincipalGroupIdentifier(StrTestHelper::generateUuid());
        $roleIdentifier = new RoleIdentifier(StrTestHelper::generateUuid());

        $input = new CreatePrincipalInput(
            $identityIdentifier,
            $accountIdentifier,
        );

        $expectedPrincipal = new Principal(
            $principalIdentifier,
            $identityIdentifier,
            null,
            [],
            [],
        );

        $defaultPrincipalGroup = new PrincipalGroup(
            $principalGroupIdentifier,
            $accountIdentifier,
            'Default',
            true,
            new DateTimeImmutable(),
        );

        $agencyAccount = $this->createAccount(AccountCategory::AGENCY);
        $agencyActorRole = $this->createRole($roleIdentifier, 'AGENCY_ACTOR');

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findByIdentityIdentifier')
            ->with($identityIdentifier)
            ->once()
            ->andReturn(null);

        $principalFactory = Mockery::mock(PrincipalFactoryInterface::class);
        $principalFactory->shouldReceive('create')
            ->with($identityIdentifier)
            ->once()
            ->andReturn($expectedPrincipal);

        $principalRepository->shouldReceive('save')
            ->with($expectedPrincipal)
            ->once();

        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $principalGroupRepository->shouldReceive('findDefaultByAccountId')
            ->with($accountIdentifier)
            ->once()
            ->andReturn(null);

        $principalGroupFactory = Mockery::mock(PrincipalGroupFactoryInterface::class);
        $principalGroupFactory->shouldReceive('create')
            ->with($accountIdentifier, 'Default', true)
            ->once()
            ->andReturn($defaultPrincipalGroup);

        $principalGroupRepository->shouldReceive('save')
            ->with($defaultPrincipalGroup)
            ->twice();

        $accountRepository = Mockery::mock(AccountRepositoryInterface::class);
        $accountRepository->shouldReceive('findById')
            ->with($accountIdentifier)
            ->once()
            ->andReturn($agencyAccount);

        $roleRepository = Mockery::mock(RoleRepositoryInterface::class);
        $roleRepository->shouldReceive('findByName')
            ->with('AGENCY_ACTOR')
            ->once()
            ->andReturn($agencyActorRole);

        $this->app->instance(PrincipalFactoryInterface::class, $principalFactory);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(PrincipalGroupFactoryInterface::class, $principalGroupFactory);
        $this->app->instance(PrincipalGroupRepositoryInterface::class, $principalGroupRepository);
        $this->app->instance(AccountRepositoryInterface::class, $accountRepository);
        $this->app->instance(RoleRepositoryInterface::class, $roleRepository);
        $useCase = $this->app->make(CreatePrincipalInterface::class);
        $result = $useCase->process($input);

        $this->assertSame((string) $principalIdentifier, (string) $result->principalIdentifier());
        $this->assertTrue($defaultPrincipalGroup->hasRole($roleIdentifier));
    }

    /**
     * 正常系: AccountがTALENTの場合、TALENT_ACTOR_ROLEが付与されること.
     *
     * @return void
     * @throws PrincipalAlreadyExistsException
     * @throws BindingResolutionException
     */
    public function testProcessWithTalentAccountAssignsTalentActorRole(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principalGroupIdentifier = new PrincipalGroupIdentifier(StrTestHelper::generateUuid());
        $roleIdentifier = new RoleIdentifier(StrTestHelper::generateUuid());

        $input = new CreatePrincipalInput(
            $identityIdentifier,
            $accountIdentifier,
        );

        $expectedPrincipal = new Principal(
            $principalIdentifier,
            $identityIdentifier,
            null,
            [],
            [],
        );

        $defaultPrincipalGroup = new PrincipalGroup(
            $principalGroupIdentifier,
            $accountIdentifier,
            'Default',
            true,
            new DateTimeImmutable(),
        );

        $talentAccount = $this->createAccount(AccountCategory::TALENT);
        $talentActorRole = $this->createRole($roleIdentifier, 'TALENT_ACTOR');

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findByIdentityIdentifier')
            ->with($identityIdentifier)
            ->once()
            ->andReturn(null);

        $principalFactory = Mockery::mock(PrincipalFactoryInterface::class);
        $principalFactory->shouldReceive('create')
            ->with($identityIdentifier)
            ->once()
            ->andReturn($expectedPrincipal);

        $principalRepository->shouldReceive('save')
            ->with($expectedPrincipal)
            ->once();

        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $principalGroupRepository->shouldReceive('findDefaultByAccountId')
            ->with($accountIdentifier)
            ->once()
            ->andReturn(null);

        $principalGroupFactory = Mockery::mock(PrincipalGroupFactoryInterface::class);
        $principalGroupFactory->shouldReceive('create')
            ->with($accountIdentifier, 'Default', true)
            ->once()
            ->andReturn($defaultPrincipalGroup);

        $principalGroupRepository->shouldReceive('save')
            ->with($defaultPrincipalGroup)
            ->twice();

        $accountRepository = Mockery::mock(AccountRepositoryInterface::class);
        $accountRepository->shouldReceive('findById')
            ->with($accountIdentifier)
            ->once()
            ->andReturn($talentAccount);

        $roleRepository = Mockery::mock(RoleRepositoryInterface::class);
        $roleRepository->shouldReceive('findByName')
            ->with('TALENT_ACTOR')
            ->once()
            ->andReturn($talentActorRole);

        $this->app->instance(PrincipalFactoryInterface::class, $principalFactory);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(PrincipalGroupFactoryInterface::class, $principalGroupFactory);
        $this->app->instance(PrincipalGroupRepositoryInterface::class, $principalGroupRepository);
        $this->app->instance(AccountRepositoryInterface::class, $accountRepository);
        $this->app->instance(RoleRepositoryInterface::class, $roleRepository);
        $useCase = $this->app->make(CreatePrincipalInterface::class);
        $result = $useCase->process($input);

        $this->assertSame((string) $principalIdentifier, (string) $result->principalIdentifier());
        $this->assertTrue($defaultPrincipalGroup->hasRole($roleIdentifier));
    }

    /**
     * 正常系: AccountがGENERALの場合、COLLABORATOR_ROLEが付与されること.
     *
     * @return void
     * @throws PrincipalAlreadyExistsException
     * @throws BindingResolutionException
     */
    public function testProcessWithGeneralAccountAssignsCollaboratorRole(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principalGroupIdentifier = new PrincipalGroupIdentifier(StrTestHelper::generateUuid());
        $roleIdentifier = new RoleIdentifier(StrTestHelper::generateUuid());

        $input = new CreatePrincipalInput(
            $identityIdentifier,
            $accountIdentifier,
        );

        $expectedPrincipal = new Principal(
            $principalIdentifier,
            $identityIdentifier,
            null,
            [],
            [],
        );

        $defaultPrincipalGroup = new PrincipalGroup(
            $principalGroupIdentifier,
            $accountIdentifier,
            'Default',
            true,
            new DateTimeImmutable(),
        );

        $generalAccount = $this->createAccount(AccountCategory::GENERAL);
        $collaboratorRole = $this->createRole($roleIdentifier, 'COLLABORATOR');

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findByIdentityIdentifier')
            ->with($identityIdentifier)
            ->once()
            ->andReturn(null);

        $principalFactory = Mockery::mock(PrincipalFactoryInterface::class);
        $principalFactory->shouldReceive('create')
            ->with($identityIdentifier)
            ->once()
            ->andReturn($expectedPrincipal);

        $principalRepository->shouldReceive('save')
            ->with($expectedPrincipal)
            ->once();

        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $principalGroupRepository->shouldReceive('findDefaultByAccountId')
            ->with($accountIdentifier)
            ->once()
            ->andReturn(null);

        $principalGroupFactory = Mockery::mock(PrincipalGroupFactoryInterface::class);
        $principalGroupFactory->shouldReceive('create')
            ->with($accountIdentifier, 'Default', true)
            ->once()
            ->andReturn($defaultPrincipalGroup);

        $principalGroupRepository->shouldReceive('save')
            ->with($defaultPrincipalGroup)
            ->twice();

        $accountRepository = Mockery::mock(AccountRepositoryInterface::class);
        $accountRepository->shouldReceive('findById')
            ->with($accountIdentifier)
            ->once()
            ->andReturn($generalAccount);

        $roleRepository = Mockery::mock(RoleRepositoryInterface::class);
        $roleRepository->shouldReceive('findByName')
            ->with('COLLABORATOR')
            ->once()
            ->andReturn($collaboratorRole);

        $this->app->instance(PrincipalFactoryInterface::class, $principalFactory);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(PrincipalGroupFactoryInterface::class, $principalGroupFactory);
        $this->app->instance(PrincipalGroupRepositoryInterface::class, $principalGroupRepository);
        $this->app->instance(AccountRepositoryInterface::class, $accountRepository);
        $this->app->instance(RoleRepositoryInterface::class, $roleRepository);
        $useCase = $this->app->make(CreatePrincipalInterface::class);
        $result = $useCase->process($input);

        $this->assertSame((string) $principalIdentifier, (string) $result->principalIdentifier());
        $this->assertTrue($defaultPrincipalGroup->hasRole($roleIdentifier));
    }

    /**
     * 正常系: Roleが見つからない場合、Roleはアタッチされないこと.
     *
     * @return void
     * @throws PrincipalAlreadyExistsException
     * @throws BindingResolutionException
     */
    public function testProcessWithRoleNotFoundDoesNotAttachRole(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principalGroupIdentifier = new PrincipalGroupIdentifier(StrTestHelper::generateUuid());

        $input = new CreatePrincipalInput(
            $identityIdentifier,
            $accountIdentifier,
        );

        $expectedPrincipal = new Principal(
            $principalIdentifier,
            $identityIdentifier,
            null,
            [],
            [],
        );

        $defaultPrincipalGroup = new PrincipalGroup(
            $principalGroupIdentifier,
            $accountIdentifier,
            'Default',
            true,
            new DateTimeImmutable(),
        );

        $agencyAccount = $this->createAccount(AccountCategory::AGENCY);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findByIdentityIdentifier')
            ->with($identityIdentifier)
            ->once()
            ->andReturn(null);

        $principalFactory = Mockery::mock(PrincipalFactoryInterface::class);
        $principalFactory->shouldReceive('create')
            ->with($identityIdentifier)
            ->once()
            ->andReturn($expectedPrincipal);

        $principalRepository->shouldReceive('save')
            ->with($expectedPrincipal)
            ->once();

        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $principalGroupRepository->shouldReceive('findDefaultByAccountId')
            ->with($accountIdentifier)
            ->once()
            ->andReturn(null);

        $principalGroupFactory = Mockery::mock(PrincipalGroupFactoryInterface::class);
        $principalGroupFactory->shouldReceive('create')
            ->with($accountIdentifier, 'Default', true)
            ->once()
            ->andReturn($defaultPrincipalGroup);

        $principalGroupRepository->shouldReceive('save')
            ->with($defaultPrincipalGroup)
            ->twice();

        $accountRepository = Mockery::mock(AccountRepositoryInterface::class);
        $accountRepository->shouldReceive('findById')
            ->with($accountIdentifier)
            ->once()
            ->andReturn($agencyAccount);

        $roleRepository = Mockery::mock(RoleRepositoryInterface::class);
        $roleRepository->shouldReceive('findByName')
            ->with('AGENCY_ACTOR')
            ->once()
            ->andReturnNull();

        $this->app->instance(PrincipalFactoryInterface::class, $principalFactory);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(PrincipalGroupFactoryInterface::class, $principalGroupFactory);
        $this->app->instance(PrincipalGroupRepositoryInterface::class, $principalGroupRepository);
        $this->app->instance(AccountRepositoryInterface::class, $accountRepository);
        $this->app->instance(RoleRepositoryInterface::class, $roleRepository);
        $useCase = $this->app->make(CreatePrincipalInterface::class);
        $result = $useCase->process($input);

        $this->assertSame((string) $principalIdentifier, (string) $result->principalIdentifier());
        $this->assertEmpty($defaultPrincipalGroup->roles());
    }

    private function createAccount(AccountCategory $category): Account
    {
        $address = new BillingAddress(
            countryCode: CountryCode::JAPAN,
            postalCode: new PostalCode('100-0001'),
            stateOrProvince: new StateOrProvince('Tokyo'),
            city: new City('Chiyoda'),
            addressLine1: new AddressLine('1-1-1'),
        );
        $contact = new BillingContact(
            name: new ContractName('Taro Example'),
            email: new Email('taro@example.com'),
        );
        $plan = new Plan(
            planName: new PlanName('Basic Plan'),
            billingCycle: BillingCycle::MONTHLY,
            planDescription: new PlanDescription(''),
            money: new Money(10000, Currency::KRW),
        );
        $taxInfo = new TaxInfo(TaxRegion::JP, TaxCategory::TAXABLE, 'T1234567890123');
        $contractInfo = new ContractInfo(
            billingAddress: $address,
            billingContact: $contact,
            billingMethod: BillingMethod::INVOICE,
            plan: $plan,
            taxInfo: $taxInfo,
        );

        return new Account(
            new AccountIdentifier(StrTestHelper::generateUuid()),
            new Email('test@example.com'),
            AccountType::CORPORATION,
            new AccountName('Test Account'),
            $contractInfo,
            AccountStatus::ACTIVE,
            $category,
            DeletionReadinessChecklist::ready(),
        );
    }

    private function createRole(RoleIdentifier $roleIdentifier, string $name): Role
    {
        return new Role(
            $roleIdentifier,
            $name,
            [],
            true,
            new DateTimeImmutable(),
        );
    }
}
