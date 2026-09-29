<?php

declare(strict_types=1);

namespace Tests\Identity\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Identity\Domain\ValueObject\StepUpAuthenticationScope;

class StepUpAuthenticationScopeTest extends TestCase
{
    public function testItDefinesPasskeyManagementStorageValue(): void
    {
        $this->assertSame('passkey.manage', StepUpAuthenticationScope::PASSKEY_MANAGE->value);
        $this->assertSame(StepUpAuthenticationScope::PASSKEY_MANAGE, StepUpAuthenticationScope::from('passkey.manage'));
    }
}
