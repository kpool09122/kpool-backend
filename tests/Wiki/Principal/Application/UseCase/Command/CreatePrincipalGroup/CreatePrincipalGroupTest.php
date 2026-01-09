<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Application\UseCase\Command\CreatePrincipalGroup;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Wiki\Principal\Application\UseCase\Command\CreatePrincipalGroup\CreatePrincipalGroup;
use Source\Wiki\Principal\Application\UseCase\Command\CreatePrincipalGroup\CreatePrincipalGroupInput;
use Source\Wiki\Principal\Application\UseCase\Command\CreatePrincipalGroup\CreatePrincipalGroupInterface;
use Source\Wiki\Principal\Domain\Entity\PrincipalGroup;
use Source\Wiki\Principal\Domain\Factory\PrincipalGroupFactoryInterface;
use Source\Wiki\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Wiki\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
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
                false,
            )
            ->andReturn($testData->principalGroup);

        $this->app->instance(PrincipalGroupRepositoryInterface::class, $repository);
        $this->app->instance(PrincipalGroupFactoryInterface::class, $factory);

        $useCase = $this->app->make(CreatePrincipalGroupInterface::class);

        $principalGroup = $useCase->process($testData->input);

        $this->assertSame((string) $testData->principalGroupIdentifier, (string) $principalGroup->principalGroupIdentifier());
        $this->assertSame((string) $testData->accountIdentifier, (string) $principalGroup->accountIdentifier());
        $this->assertSame($testData->name, $principalGroup->name());
        $this->assertFalse($principalGroup->isDefault());
    }

    private function createDummyTestData(): CreatePrincipalGroupTestData
    {
        $principalGroupIdentifier = new PrincipalGroupIdentifier(StrTestHelper::generateUuid());
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $name = 'Test Principal Group';

        $principalGroup = new PrincipalGroup(
            $principalGroupIdentifier,
            $accountIdentifier,
            $name,
            false,
            new DateTimeImmutable(),
        );

        $input = new CreatePrincipalGroupInput(
            $accountIdentifier,
            $name,
        );

        return new CreatePrincipalGroupTestData(
            $principalGroupIdentifier,
            $accountIdentifier,
            $name,
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
        public PrincipalGroup $principalGroup,
        public CreatePrincipalGroupInput $input,
    ) {
    }
}
