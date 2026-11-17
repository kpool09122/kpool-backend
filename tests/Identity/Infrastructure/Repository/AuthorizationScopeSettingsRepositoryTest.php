<?php

declare(strict_types=1);

namespace Tests\Identity\Infrastructure\Repository;

use PHPUnit\Framework\Attributes\Group;
use Source\Identity\Domain\Entity\AuthorizationScopeSettings;
use Source\Identity\Domain\Repository\AuthorizationScopeSettingsRepositoryInterface;
use Source\Identity\Domain\ValueObject\AuthorizationScope;
use Source\Identity\Domain\ValueObject\AuthorizationScopeSettingsIdentifier;
use Source\Identity\Infrastructure\Repository\AuthorizationScopeSettingsRepository;
use Tests\Helper\CreateAuthorizationConnection;
use Tests\TestCase;

class AuthorizationScopeSettingsRepositoryTest extends TestCase
{
    public function testBinding(): void
    {
        $this->assertInstanceOf(AuthorizationScopeSettingsRepository::class, app(AuthorizationScopeSettingsRepositoryInterface::class));
    }

    #[Group('useDb')]
    public function testRoundTripUpdateAndIsolation(): void
    {
        $first = CreateAuthorizationConnection::create()->identityIdentifier();
        $second = CreateAuthorizationConnection::create()->identityIdentifier();
        $repository = app(AuthorizationScopeSettingsRepositoryInterface::class);
        $this->assertNull($repository->find($first));
        $settings = new AuthorizationScopeSettings(new AuthorizationScopeSettingsIdentifier('0199f700-0000-7000-8000-000000000098'), $first, [AuthorizationScope::DraftRead, AuthorizationScope::WikiRead]);
        $repository->save($settings);
        $this->assertEquals($settings, $repository->find($first));
        $repository->save(new AuthorizationScopeSettings(new AuthorizationScopeSettingsIdentifier('0199f700-0000-7000-8000-000000000098'), $first, []));
        $this->assertSame([], $repository->find($first)?->scopes());
        $this->assertNull($repository->find($second));
    }
}
