<?php

declare(strict_types=1);

namespace Tests\Identity\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Identity\Domain\ValueObject\AuthorizationScope;

class AuthorizationScopeTest extends TestCase
{
    public function testPersistedScopesAreTheSixAgreedCapabilities(): void
    {
        $this->assertSame(['wiki:read', 'wiki-drafts:read', 'wiki-drafts:write', 'wiki-drafts:submit', 'wiki-images:read', 'wiki-images:write'], array_map(static fn (AuthorizationScope $scope): string => $scope->value, AuthorizationScope::cases()));
        $this->assertNull(AuthorizationScope::tryFrom('*'));
    }
}
