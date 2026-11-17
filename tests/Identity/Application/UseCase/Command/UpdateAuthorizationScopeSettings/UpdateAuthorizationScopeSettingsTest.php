<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\UpdateAuthorizationScopeSettings;

use InvalidArgumentException;
use Mockery;
use Mockery\MockInterface;
use Source\Identity\Application\UseCase\Command\UpdateAuthorizationScopeSettings\UpdateAuthorizationScopeSettings;
use Source\Identity\Application\UseCase\Command\UpdateAuthorizationScopeSettings\UpdateAuthorizationScopeSettingsInput;
use Source\Identity\Application\UseCase\Command\UpdateAuthorizationScopeSettings\UpdateAuthorizationScopeSettingsOutput;
use Source\Identity\Domain\Entity\AuthorizationScopeSettings;
use Source\Identity\Domain\Factory\AuthorizationScopeSettingsFactoryInterface;
use Source\Identity\Domain\Repository\AuthorizationScopeSettingsRepositoryInterface;
use Source\Identity\Domain\ValueObject\AuthorizationScope;
use Source\Identity\Domain\ValueObject\AuthorizationScopeSettingsIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\TestCase;

class UpdateAuthorizationScopeSettingsTest extends TestCase
{
    public function testSavesOwnerAndExactScopes(): void
    {
        $id = new IdentityIdentifier('0199f700-0000-7000-8000-000000000099');
        /** @var AuthorizationScopeSettingsRepositoryInterface&MockInterface $repository */
        $repository = Mockery::mock(AuthorizationScopeSettingsRepositoryInterface::class);
        $repository->shouldReceive('find')->once()->andReturnNull();
        $settings = new AuthorizationScopeSettings(new AuthorizationScopeSettingsIdentifier('0199f700-0000-7000-8000-000000000098'), $id, [AuthorizationScope::WikiRead]);
        /** @var AuthorizationScopeSettingsFactoryInterface&MockInterface $factory */
        $factory = Mockery::mock(AuthorizationScopeSettingsFactoryInterface::class);
        $factory->shouldReceive('create')->once()->with($id, [AuthorizationScope::WikiRead])->andReturn($settings);
        $repository->shouldReceive('save')->once()->with($settings);
        $useCase = new UpdateAuthorizationScopeSettings($factory, $repository);
        $useCase->process(new UpdateAuthorizationScopeSettingsInput($id, [AuthorizationScope::WikiRead]), new UpdateAuthorizationScopeSettingsOutput());
    }

    public function testUpdatesExistingSettingsWithoutCreatingANewIdentity(): void
    {
        $id = new IdentityIdentifier('0199f700-0000-7000-8000-000000000099');
        $identifier = new AuthorizationScopeSettingsIdentifier('0199f700-0000-7000-8000-000000000098');
        $settings = new AuthorizationScopeSettings($identifier, $id, [AuthorizationScope::WikiRead]);
        /** @var AuthorizationScopeSettingsRepositoryInterface&MockInterface $repository */
        $repository = Mockery::mock(AuthorizationScopeSettingsRepositoryInterface::class);
        $repository->shouldReceive('find')->once()->with($id)->andReturn($settings);
        $repository->shouldReceive('save')->once()->with($settings);
        /** @var AuthorizationScopeSettingsFactoryInterface&MockInterface $factory */
        $factory = Mockery::mock(AuthorizationScopeSettingsFactoryInterface::class);
        $factory->shouldNotReceive('create');

        $useCase = new UpdateAuthorizationScopeSettings($factory, $repository);
        $useCase->process(new UpdateAuthorizationScopeSettingsInput($id, []), new UpdateAuthorizationScopeSettingsOutput());

        $this->assertSame($identifier, $settings->identifier());
        $this->assertSame($id, $settings->identityIdentifier());
        $this->assertSame([], $settings->scopes());
    }

    public function testRejectsDuplicateScopesWithoutSaving(): void
    {
        /** @var AuthorizationScopeSettingsRepositoryInterface&MockInterface $repository */
        $repository = Mockery::mock(AuthorizationScopeSettingsRepositoryInterface::class);
        $repository->shouldReceive('find')->once()->andReturnNull();
        $repository->shouldNotReceive('save');
        $useCase = new UpdateAuthorizationScopeSettings(app(AuthorizationScopeSettingsFactoryInterface::class), $repository);
        $this->expectException(InvalidArgumentException::class);
        $useCase->process(new UpdateAuthorizationScopeSettingsInput(new IdentityIdentifier('0199f700-0000-7000-8000-000000000099'), [AuthorizationScope::WikiRead, AuthorizationScope::WikiRead]), new UpdateAuthorizationScopeSettingsOutput());
    }
}
