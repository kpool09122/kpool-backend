<?php

declare(strict_types=1);

namespace Tests\Account\Application\UseCase\Command\AddIdentityToIdentityGroup;

use DateTimeImmutable;
use DomainException;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Account\Application\Exception\IdentityGroupNotFoundException;
use Source\Account\Application\UseCase\Command\AddIdentityToIdentityGroup\AddIdentityToIdentityGroup;
use Source\Account\Application\UseCase\Command\AddIdentityToIdentityGroup\AddIdentityToIdentityGroupInput;
use Source\Account\Application\UseCase\Command\AddIdentityToIdentityGroup\AddIdentityToIdentityGroupInterface;
use Source\Account\Domain\Entity\IdentityGroup;
use Source\Account\Domain\Repository\IdentityGroupRepositoryInterface;
use Source\Account\Domain\ValueObject\AccountRole;
use Source\Account\Domain\ValueObject\IdentityGroupIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class AddIdentityToIdentityGroupTest extends TestCase
{
    /**
     * @return void
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $repository = Mockery::mock(IdentityGroupRepositoryInterface::class);
        $this->app->instance(IdentityGroupRepositoryInterface::class, $repository);
        $useCase = $this->app->make(AddIdentityToIdentityGroupInterface::class);
        $this->assertInstanceOf(AddIdentityToIdentityGroup::class, $useCase);
    }

    /**
     * @return void
     * @throws BindingResolutionException
     */
    public function testProcess(): void
    {
        $identityGroupIdentifier = new IdentityGroupIdentifier(StrTestHelper::generateUuid());
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());

        $identityGroup = new IdentityGroup(
            $identityGroupIdentifier,
            $accountIdentifier,
            'Test Group',
            AccountRole::MEMBER,
            false,
            new DateTimeImmutable(),
        );

        $repository = Mockery::mock(IdentityGroupRepositoryInterface::class);
        $repository->shouldReceive('findById')
            ->once()
            ->with(Mockery::on(fn ($arg) => (string) $arg === (string) $identityGroupIdentifier))
            ->andReturn($identityGroup);
        $repository->shouldReceive('save')
            ->once()
            ->with(Mockery::on(function (IdentityGroup $arg) use ($identityIdentifier) {
                return $arg->hasMember($identityIdentifier);
            }))
            ->andReturnNull();

        $this->app->instance(IdentityGroupRepositoryInterface::class, $repository);

        $useCase = $this->app->make(AddIdentityToIdentityGroupInterface::class);
        $input = new AddIdentityToIdentityGroupInput($identityGroupIdentifier, $identityIdentifier);

        $result = $useCase->process($input);

        $this->assertTrue($result->hasMember($identityIdentifier));
    }

    /**
     * @return void
     * @throws BindingResolutionException
     */
    public function testThrowsWhenNotFound(): void
    {
        $identityGroupIdentifier = new IdentityGroupIdentifier(StrTestHelper::generateUuid());
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());

        $repository = Mockery::mock(IdentityGroupRepositoryInterface::class);
        $repository->shouldReceive('findById')
            ->once()
            ->with(Mockery::on(fn ($arg) => (string) $arg === (string) $identityGroupIdentifier))
            ->andReturnNull();
        $repository->shouldNotReceive('save');

        $this->app->instance(IdentityGroupRepositoryInterface::class, $repository);

        $useCase = $this->app->make(AddIdentityToIdentityGroupInterface::class);
        $input = new AddIdentityToIdentityGroupInput($identityGroupIdentifier, $identityIdentifier);

        $this->expectException(IdentityGroupNotFoundException::class);

        $useCase->process($input);
    }

    /**
     * @return void
     * @throws BindingResolutionException
     */
    public function testThrowsWhenAlreadyMember(): void
    {
        $identityGroupIdentifier = new IdentityGroupIdentifier(StrTestHelper::generateUuid());
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());

        $identityGroup = new IdentityGroup(
            $identityGroupIdentifier,
            $accountIdentifier,
            'Test Group',
            AccountRole::MEMBER,
            false,
            new DateTimeImmutable(),
        );
        $identityGroup->addMember($identityIdentifier);

        $repository = Mockery::mock(IdentityGroupRepositoryInterface::class);
        $repository->shouldReceive('findById')
            ->once()
            ->with(Mockery::on(fn ($arg) => (string) $arg === (string) $identityGroupIdentifier))
            ->andReturn($identityGroup);
        $repository->shouldNotReceive('save');

        $this->app->instance(IdentityGroupRepositoryInterface::class, $repository);

        $useCase = $this->app->make(AddIdentityToIdentityGroupInterface::class);
        $input = new AddIdentityToIdentityGroupInput($identityGroupIdentifier, $identityIdentifier);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Identity is already a member of this group.');

        $useCase->process($input);
    }
}
