<?php

declare(strict_types=1);

namespace Tests\Account\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Account\Domain\ValueObject\AffiliationIdentifier;
use Tests\Helper\StrTestHelper;

class AffiliationIdentifierTest extends TestCase
{
    public function test__construct(): void
    {
        $uuid = StrTestHelper::generateUuid();
        $identifier = new AffiliationIdentifier($uuid);
        $this->assertSame($uuid, (string) $identifier);
    }

    public function testInvalidUuid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new AffiliationIdentifier('invalid-uuid');
    }

    public function testEmptyString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new AffiliationIdentifier('');
    }
}
