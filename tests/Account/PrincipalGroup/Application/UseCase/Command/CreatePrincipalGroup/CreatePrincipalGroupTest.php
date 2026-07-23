<?php

declare(strict_types=1);

namespace Tests\Account\PrincipalGroup\Application\UseCase\Command\CreatePrincipalGroup;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Account\Principal\Application\UseCase\Command\CreatePrincipalGroup\CreatePrincipalGroup;
use Source\Account\Principal\Application\UseCase\Command\CreatePrincipalGroup\CreatePrincipalGroupInput;
use Source\Account\Principal\Application\UseCase\Command\CreatePrincipalGroup\CreatePrincipalGroupInterface;
use Source\Account\Principal\Application\UseCase\Command\CreatePrincipalGroup\CreatePrincipalGroupOutput;
use Source\Account\Principal\Domain\Entity\PrincipalGroup;
use Source\Account\Principal\Domain\Factory\PrincipalGroupFactoryInterface;
use Source\Account\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Account\Principal\Domain\ValueObject\AccountRole;
use Source\Account\Shared\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class CreatePrincipalGroupTest extends TestCase
{
    /**
     * @return void
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $repository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $factory = Mockery::mock(PrincipalGroupFactoryInterface::class);
        $this->app->instance(PrincipalGroupRepositoryInterface::class, $repository);
        $this->app->instance(PrincipalGroupFactoryInterface::class, $factory);
        $useCase = $this->app->make(CreatePrincipalGroupInterface::class);
        $this->assertInstanceOf(CreatePrincipalGroup::class, $useCase);
    }

    /**
     * @return void
     * @throws BindingResolutionException
     */
    public function testProcess(): void
    {
        $testData = $this->createDummyTestData();

        $repository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $repository->shouldReceive('save')
            ->once()
            ->with($testData->principalGroup)
            ->andReturnNull();

        $factory = Mockery::mock(PrincipalGroupFactoryInterface::class);
        $factory->shouldReceive('create')
            ->once()
            ->with(
                $testData->accountIdentifier,
                $testData->name,
                $testData->role,
                false
            )
            ->andReturn($testData->principalGroup);

        $this->app->instance(PrincipalGroupRepositoryInterface::class, $repository);
        $this->app->instance(PrincipalGroupFactoryInterface::class, $factory);

        $useCase = $this->app->make(CreatePrincipalGroupInterface::class);

        $output = new CreatePrincipalGroupOutput();
        $useCase->process($testData->input, $output);

        $result = $output->toArray();
        $this->assertSame((string) $testData->principalGroupIdentifier, $result['principalGroupIdentifier']);
        $this->assertSame((string) $testData->accountIdentifier, $result['accountIdentifier']);
        $this->assertSame($testData->name, $result['name']);
        $this->assertSame($testData->role->value, $result['role']);
        $this->assertFalse($result['isDefault']);
    }

    private function createDummyTestData(): CreatePrincipalGroupTestData
    {
        $principalGroupIdentifier = new PrincipalGroupIdentifier(StrTestHelper::generateUuid());
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $name = 'Talent X Responsible Team';
        $role = AccountRole::MEMBER;

        $principalGroup = new PrincipalGroup(
            $principalGroupIdentifier,
            $accountIdentifier,
            $name,
            $role,
            false,
            new DateTimeImmutable(),
        );

        $input = new CreatePrincipalGroupInput(
            $accountIdentifier,
            $name,
            $role,
        );

        return new CreatePrincipalGroupTestData(
            $principalGroupIdentifier,
            $accountIdentifier,
            $name,
            $role,
            $principalGroup,
            $input,
        );
    }
}

readonly class CreatePrincipalGroupTestData
{
    public function __construct(
        public PrincipalGroupIdentifier $principalGroupIdentifier,
        public AccountIdentifier $accountIdentifier,
        public string $name,
        public AccountRole $role,
        public PrincipalGroup $principalGroup,
        public CreatePrincipalGroupInput $input,
    ) {
    }
}
