<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Application\UseCase\Command\CreateRole;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Wiki\Principal\Application\UseCase\Command\CreateRole\CreateRole;
use Source\Wiki\Principal\Application\UseCase\Command\CreateRole\CreateRoleInput;
use Source\Wiki\Principal\Application\UseCase\Command\CreateRole\CreateRoleInterface;
use Source\Wiki\Principal\Application\UseCase\Command\CreateRole\CreateRoleOutput;
use Source\Wiki\Principal\Domain\Entity\Role;
use Source\Wiki\Principal\Domain\Factory\RoleFactoryInterface;
use Source\Wiki\Principal\Domain\Repository\RoleRepositoryInterface;
use Source\Wiki\Principal\Domain\ValueObject\PolicyIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\RoleIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class CreateRoleTest extends TestCase
{
    /**
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $repository = Mockery::mock(RoleRepositoryInterface::class);
        $factory = Mockery::mock(RoleFactoryInterface::class);
        $this->app->instance(RoleRepositoryInterface::class, $repository);
        $this->app->instance(RoleFactoryInterface::class, $factory);
        $useCase = $this->app->make(CreateRoleInterface::class);
        $this->assertInstanceOf(CreateRole::class, $useCase);
    }

    /**
     * @throws BindingResolutionException
     */
    public function testProcess(): void
    {
        $testData = $this->createDummyTestData();

        $repository = Mockery::mock(RoleRepositoryInterface::class);
        $repository->shouldReceive('save')
            ->once()
            ->with($testData->role)
            ->andReturnNull();

        $factory = Mockery::mock(RoleFactoryInterface::class);
        $factory->shouldReceive('create')
            ->once()
            ->with(
                $testData->name,
                $testData->policies,
                $testData->isSystemRole,
            )
            ->andReturn($testData->role);

        $this->app->instance(RoleRepositoryInterface::class, $repository);
        $this->app->instance(RoleFactoryInterface::class, $factory);

        $useCase = $this->app->make(CreateRoleInterface::class);
        $output = new CreateRoleOutput();

        $useCase->process($testData->input, $output);

        $result = $output->toArray();
        $this->assertSame((string) $testData->roleIdentifier, $result['roleIdentifier']);
        $this->assertSame($testData->name, $result['name']);
        $this->assertSame($testData->isSystemRole, $result['isSystemRole']);
    }

    private function createDummyTestData(): CreateRoleTestData
    {
        $roleIdentifier = new RoleIdentifier(StrTestHelper::generateUuid());
        $name = 'Test Role';
        $policies = [
            new PolicyIdentifier(StrTestHelper::generateUuid()),
        ];
        $isSystemRole = false;

        $role = new Role(
            $roleIdentifier,
            $name,
            $policies,
            $isSystemRole,
            new DateTimeImmutable(),
        );

        $input = new CreateRoleInput(
            $name,
            $policies,
            $isSystemRole,
        );

        return new CreateRoleTestData(
            $roleIdentifier,
            $name,
            $policies,
            $isSystemRole,
            $role,
            $input,
        );
    }
}

readonly class CreateRoleTestData
{
    /**
     * @param PolicyIdentifier[] $policies
     */
    public function __construct(
        public RoleIdentifier $roleIdentifier,
        public string $name,
        public array $policies,
        public bool $isSystemRole,
        public Role $role,
        public CreateRoleInput $input,
    ) {
    }
}
