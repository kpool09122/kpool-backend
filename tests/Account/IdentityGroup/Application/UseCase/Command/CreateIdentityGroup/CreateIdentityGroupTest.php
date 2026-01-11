<?php

declare(strict_types=1);

namespace Tests\Account\IdentityGroup\Application\UseCase\Command\CreateIdentityGroup;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Account\IdentityGroup\Application\UseCase\Command\CreateIdentityGroup\CreateIdentityGroup;
use Source\Account\IdentityGroup\Application\UseCase\Command\CreateIdentityGroup\CreateIdentityGroupInput;
use Source\Account\IdentityGroup\Application\UseCase\Command\CreateIdentityGroup\CreateIdentityGroupInterface;
use Source\Account\IdentityGroup\Domain\Entity\IdentityGroup;
use Source\Account\IdentityGroup\Domain\Factory\IdentityGroupFactoryInterface;
use Source\Account\IdentityGroup\Domain\Repository\IdentityGroupRepositoryInterface;
use Source\Account\IdentityGroup\Domain\ValueObject\AccountRole;
use Source\Account\Shared\Domain\ValueObject\IdentityGroupIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class CreateIdentityGroupTest extends TestCase
{
    /**
     * @return void
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $repository = Mockery::mock(IdentityGroupRepositoryInterface::class);
        $factory = Mockery::mock(IdentityGroupFactoryInterface::class);
        $this->app->instance(IdentityGroupRepositoryInterface::class, $repository);
        $this->app->instance(IdentityGroupFactoryInterface::class, $factory);
        $useCase = $this->app->make(CreateIdentityGroupInterface::class);
        $this->assertInstanceOf(CreateIdentityGroup::class, $useCase);
    }

    /**
     * @return void
     * @throws BindingResolutionException
     */
    public function testProcess(): void
    {
        $testData = $this->createDummyTestData();

        $repository = Mockery::mock(IdentityGroupRepositoryInterface::class);
        $repository->shouldReceive('save')
            ->once()
            ->with($testData->identityGroup)
            ->andReturnNull();

        $factory = Mockery::mock(IdentityGroupFactoryInterface::class);
        $factory->shouldReceive('create')
            ->once()
            ->with(
                $testData->accountIdentifier,
                $testData->name,
                $testData->role,
                false
            )
            ->andReturn($testData->identityGroup);

        $this->app->instance(IdentityGroupRepositoryInterface::class, $repository);
        $this->app->instance(IdentityGroupFactoryInterface::class, $factory);

        $useCase = $this->app->make(CreateIdentityGroupInterface::class);

        $identityGroup = $useCase->process($testData->input);

        $this->assertSame((string) $testData->identityGroupIdentifier, (string) $identityGroup->identityGroupIdentifier());
        $this->assertSame((string) $testData->accountIdentifier, (string) $identityGroup->accountIdentifier());
        $this->assertSame($testData->name, $identityGroup->name());
        $this->assertSame($testData->role, $identityGroup->role());
        $this->assertFalse($identityGroup->isDefault());
    }

    private function createDummyTestData(): CreateIdentityGroupTestData
    {
        $identityGroupIdentifier = new IdentityGroupIdentifier(StrTestHelper::generateUuid());
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $name = 'Talent X Responsible Team';
        $role = AccountRole::MEMBER;

        $identityGroup = new IdentityGroup(
            $identityGroupIdentifier,
            $accountIdentifier,
            $name,
            $role,
            false,
            new DateTimeImmutable(),
        );

        $input = new CreateIdentityGroupInput(
            $accountIdentifier,
            $name,
            $role,
        );

        return new CreateIdentityGroupTestData(
            $identityGroupIdentifier,
            $accountIdentifier,
            $name,
            $role,
            $identityGroup,
            $input,
        );
    }
}

readonly class CreateIdentityGroupTestData
{
    public function __construct(
        public IdentityGroupIdentifier $identityGroupIdentifier,
        public AccountIdentifier $accountIdentifier,
        public string $name,
        public AccountRole $role,
        public IdentityGroup $identityGroup,
        public CreateIdentityGroupInput $input,
    ) {
    }
}
