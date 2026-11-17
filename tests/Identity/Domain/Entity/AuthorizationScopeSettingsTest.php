<?php

declare(strict_types=1);

namespace Tests\Identity\Domain\Entity;

use InvalidArgumentException;
use Source\Identity\Domain\Entity\AuthorizationScopeSettings;
use Source\Identity\Domain\ValueObject\AuthorizationScope;
use Source\Identity\Domain\ValueObject\AuthorizationScopeSettingsIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\TestCase;

class AuthorizationScopeSettingsTest extends TestCase
{
    public function testPreservesOwnerAndScopesIncludingEmpty(): void
    {
        $id = new IdentityIdentifier('0199f700-0000-7000-8000-000000000099');
        $settings = new AuthorizationScopeSettings(new AuthorizationScopeSettingsIdentifier('0199f700-0000-7000-8000-000000000098'), $id, AuthorizationScope::cases());
        $this->assertSame($id, $settings->identityIdentifier());
        $this->assertSame(AuthorizationScope::cases(), $settings->scopes());
        $this->assertSame([], (new AuthorizationScopeSettings(new AuthorizationScopeSettingsIdentifier('0199f700-0000-7000-8000-000000000098'), $id, []))->scopes());
    }

    public function testRejectsDuplicateScopes(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new AuthorizationScopeSettings(new AuthorizationScopeSettingsIdentifier('0199f700-0000-7000-8000-000000000098'), new IdentityIdentifier('0199f700-0000-7000-8000-000000000099'), [AuthorizationScope::WikiRead, AuthorizationScope::WikiRead]);
    }

    public function testRejectedUpdatePreservesScopesAndIdentity(): void
    {
        $identifier = new AuthorizationScopeSettingsIdentifier('0199f700-0000-7000-8000-000000000098');
        $identity = new IdentityIdentifier('0199f700-0000-7000-8000-000000000099');
        $settings = new AuthorizationScopeSettings($identifier, $identity, [AuthorizationScope::WikiRead]);

        try {
            $settings->updateScopes([AuthorizationScope::DraftWrite, AuthorizationScope::DraftWrite]);
            $this->fail('Duplicate scopes were accepted.');
        } catch (InvalidArgumentException) {
            $this->assertSame([AuthorizationScope::WikiRead], $settings->scopes());
            $this->assertSame($identifier, $settings->identifier());
            $this->assertSame($identity, $settings->identityIdentifier());
        }
    }
}
