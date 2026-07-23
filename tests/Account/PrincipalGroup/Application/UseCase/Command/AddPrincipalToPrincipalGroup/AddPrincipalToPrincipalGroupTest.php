<?php

declare(strict_types=1);

namespace Tests\Account\PrincipalGroup\Application\UseCase\Command\AddPrincipalToPrincipalGroup;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Account\Principal\Application\Exception\PrincipalGroupNotFoundException;
use Source\Account\Principal\Application\UseCase\Command\AddPrincipalToPrincipalGroup\AddPrincipalToPrincipalGroup;
use Source\Account\Principal\Application\UseCase\Command\AddPrincipalToPrincipalGroup\AddPrincipalToPrincipalGroupInput;
use Source\Account\Principal\Application\UseCase\Command\AddPrincipalToPrincipalGroup\AddPrincipalToPrincipalGroupInterface;
use Source\Account\Principal\Application\UseCase\Command\AddPrincipalToPrincipalGroup\AddPrincipalToPrincipalGroupOutput;
use Source\Account\Principal\Domain\Entity\PrincipalGroup;
use Source\Account\Principal\Domain\Exception\PrincipalAlreadyMemberException;
use Source\Account\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Account\Principal\Domain\ValueObject\AccountRole;
use Source\Account\Shared\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class AddPrincipalToPrincipalGroupTest extends TestCase
{
    /**
     * @return void
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $repository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $this->app->instance(PrincipalGroupRepositoryInterface::class, $repository);
        $useCase = $this->app->make(AddPrincipalToPrincipalGroupInterface::class);
        $this->assertInstanceOf(AddPrincipalToPrincipalGroup::class, $useCase);
    }

    /**
     * @return void
     * @throws BindingResolutionException
     */
    public function testProcess(): void
    {
        $principalGroupIdentifier = new PrincipalGroupIdentifier(StrTestHelper::generateUuid());
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());

        $principalGroup = new PrincipalGroup(
            $principalGroupIdentifier,
            $accountIdentifier,
            'Test Group',
            AccountRole::MEMBER,
            false,
            new DateTimeImmutable(),
        );

        $repository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $repository->shouldReceive('findById')
            ->once()
            ->with(Mockery::on(fn ($arg) => (string) $arg === (string) $principalGroupIdentifier))
            ->andReturn($principalGroup);
        $repository->shouldReceive('save')
            ->once()
            ->with(Mockery::on(fn (PrincipalGroup $arg) => $arg->hasMember($principalIdentifier)))
            ->andReturnNull();

        $this->app->instance(PrincipalGroupRepositoryInterface::class, $repository);

        $useCase = $this->app->make(AddPrincipalToPrincipalGroupInterface::class);
        $input = new AddPrincipalToPrincipalGroupInput($principalGroupIdentifier, $principalIdentifier);

        $output = new AddPrincipalToPrincipalGroupOutput();
        $useCase->process($input, $output);

        $result = $output->toArray();
        $this->assertContains((string) $principalIdentifier, $result['members']);
    }

    /**
     * @return void
     * @throws BindingResolutionException
     */
    public function testThrowsWhenNotFound(): void
    {
        $principalGroupIdentifier = new PrincipalGroupIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());

        $repository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $repository->shouldReceive('findById')
            ->once()
            ->with(Mockery::on(fn ($arg) => (string) $arg === (string) $principalGroupIdentifier))
            ->andReturnNull();
        $repository->shouldNotReceive('save');

        $this->app->instance(PrincipalGroupRepositoryInterface::class, $repository);

        $useCase = $this->app->make(AddPrincipalToPrincipalGroupInterface::class);
        $input = new AddPrincipalToPrincipalGroupInput($principalGroupIdentifier, $principalIdentifier);

        $this->expectException(PrincipalGroupNotFoundException::class);

        $output = new AddPrincipalToPrincipalGroupOutput();
        $useCase->process($input, $output);
    }

    /**
     * @return void
     * @throws BindingResolutionException
     */
    public function testThrowsWhenAlreadyMember(): void
    {
        $principalGroupIdentifier = new PrincipalGroupIdentifier(StrTestHelper::generateUuid());
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());

        $principalGroup = new PrincipalGroup(
            $principalGroupIdentifier,
            $accountIdentifier,
            'Test Group',
            AccountRole::MEMBER,
            false,
            new DateTimeImmutable(),
        );
        $principalGroup->addMember($principalIdentifier);

        $repository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $repository->shouldReceive('findById')
            ->once()
            ->with(Mockery::on(fn ($arg) => (string) $arg === (string) $principalGroupIdentifier))
            ->andReturn($principalGroup);
        $repository->shouldNotReceive('save');

        $this->app->instance(PrincipalGroupRepositoryInterface::class, $repository);

        $useCase = $this->app->make(AddPrincipalToPrincipalGroupInterface::class);
        $input = new AddPrincipalToPrincipalGroupInput($principalGroupIdentifier, $principalIdentifier);

        $this->expectException(PrincipalAlreadyMemberException::class);

        $output = new AddPrincipalToPrincipalGroupOutput();
        $useCase->process($input, $output);
    }
}
