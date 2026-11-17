<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Query\GetAuthorizationScopeSettings;

use Source\Identity\Application\UseCase\Query\GetAuthorizationScopeSettings\GetAuthorizationScopeSettingsInput;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\TestCase;

class GetAuthorizationScopeSettingsInputTest extends TestCase
{
    public function testPreservesInputInstances(): void
    {
        $id = new IdentityIdentifier('0199f700-0000-7000-8000-000000000099');
        $input = new GetAuthorizationScopeSettingsInput($id);
        $this->assertSame($id, $input->identityIdentifier());
    }
}
