<?php

declare(strict_types=1);

namespace Tests\Account\Account\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Account\Account\Domain\ValueObject\AccountSnapshotIdentifier;
use Tests\Helper\StrTestHelper;

class AccountSnapshotIdentifierTest extends TestCase
{
    public function testPreservesUuid(): void
    {
        $id = StrTestHelper::generateUuid();
        self::assertSame($id, (string)new AccountSnapshotIdentifier($id));
    }

    public function testRejectsInvalidValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new AccountSnapshotIdentifier('../snapshot');
    }
}
