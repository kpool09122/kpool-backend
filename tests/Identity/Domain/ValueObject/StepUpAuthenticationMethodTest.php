<?php

declare(strict_types=1);

namespace Tests\Identity\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Identity\Domain\ValueObject\StepUpAuthenticationMethod;

class StepUpAuthenticationMethodTest extends TestCase
{
    public function testItDefinesStorageValues(): void
    {
        $this->assertSame('passkey', StepUpAuthenticationMethod::PASSKEY->value);
        $this->assertSame('sso', StepUpAuthenticationMethod::SSO->value);
        $this->assertSame(StepUpAuthenticationMethod::PASSKEY, StepUpAuthenticationMethod::from('passkey'));
        $this->assertSame(StepUpAuthenticationMethod::SSO, StepUpAuthenticationMethod::from('sso'));
    }
}
