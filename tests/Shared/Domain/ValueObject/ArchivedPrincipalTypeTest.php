<?php

declare(strict_types=1);

namespace Tests\Shared\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\ArchivedPrincipalType;

class ArchivedPrincipalTypeTest extends TestCase
{
    public function testPersistedValues(): void
    {
        $this->assertSame([
            'ACCOUNT' => 'account',
            'WIKI' => 'wiki',
        ], array_column(ArchivedPrincipalType::cases(), 'value', 'name'));
    }
}
