<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Query\GetAuthorizationScopeSettings;

use Source\Identity\Application\UseCase\Query\GetAuthorizationScopeSettings\AuthorizationScopeSettingsReadModel;
use Source\Identity\Domain\ValueObject\AuthorizationScope;
use Tests\TestCase;

class AuthorizationScopeSettingsReadModelTest extends TestCase
{
    public function testSerializesScopesAndExplicitEmpty(): void
    {
        $this->assertSame(['scopes' => []], (new AuthorizationScopeSettingsReadModel([]))->toArray());
        $this->assertSame(['scopes' => ['wiki:read']], (new AuthorizationScopeSettingsReadModel([AuthorizationScope::WikiRead]))->toArray());
    }
}
