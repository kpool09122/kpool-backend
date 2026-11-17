<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\UpdateAuthorizationScopeSettings;

use Source\Identity\Application\UseCase\Command\UpdateAuthorizationScopeSettings\UpdateAuthorizationScopeSettingsOutput;
use Tests\TestCase;

class UpdateAuthorizationScopeSettingsOutputTest extends TestCase
{
    public function testEmptyResponse(): void
    {
        $this->assertSame([], (new UpdateAuthorizationScopeSettingsOutput())->toArray());
    }
}
