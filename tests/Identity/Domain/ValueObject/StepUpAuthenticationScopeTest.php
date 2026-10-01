<?php

declare(strict_types=1);

namespace Tests\Identity\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Identity\Domain\ValueObject\StepUpAuthenticationScope;

class StepUpAuthenticationScopeTest extends TestCase
{
    public function testItDefinesRecentAuthenticationStorageValue(): void
    {
        $this->assertSame('recent_authentication', StepUpAuthenticationScope::RECENT_AUTHENTICATION->value);
        $this->assertSame(StepUpAuthenticationScope::RECENT_AUTHENTICATION, StepUpAuthenticationScope::from('recent_authentication'));
    }
}
