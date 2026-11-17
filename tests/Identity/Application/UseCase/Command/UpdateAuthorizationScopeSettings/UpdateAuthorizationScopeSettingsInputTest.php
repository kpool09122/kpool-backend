<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\UpdateAuthorizationScopeSettings;

use Source\Identity\Application\UseCase\Command\UpdateAuthorizationScopeSettings\UpdateAuthorizationScopeSettingsInput;
use Source\Identity\Domain\ValueObject\AuthorizationScope;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\TestCase;

class UpdateAuthorizationScopeSettingsInputTest extends TestCase
{
    public function testPreservesInputInstances(): void
    {
        $id = new IdentityIdentifier('0199f700-0000-7000-8000-000000000099');
        $input = new UpdateAuthorizationScopeSettingsInput($id, AuthorizationScope::cases());
        $this->assertSame($id, $input->identityIdentifier());
        $this->assertSame(AuthorizationScope::cases(), $input->scopes());
        $this->assertSame([], (new UpdateAuthorizationScopeSettingsInput($id, []))->scopes());
    }
}
