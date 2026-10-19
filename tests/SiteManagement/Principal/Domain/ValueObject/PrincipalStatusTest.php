<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Principal\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalStatus;

class PrincipalStatusTest extends TestCase
{
    public function testOnlyExplicitKnownStatusesAreRestored(): void
    {
        self::assertSame(['active', 'suspended', 'forced_stop'], array_column(PrincipalStatus::cases(), 'value'));
        self::assertSame(PrincipalStatus::ACTIVE, PrincipalStatus::from('active'));
        self::assertSame(PrincipalStatus::SUSPENDED, PrincipalStatus::from('suspended'));
        self::assertSame(PrincipalStatus::FORCED_STOP, PrincipalStatus::from('forced_stop'));
        self::assertNull(PrincipalStatus::tryFrom('unknown'));
        self::assertNull(PrincipalStatus::tryFrom(''));
    }
}
