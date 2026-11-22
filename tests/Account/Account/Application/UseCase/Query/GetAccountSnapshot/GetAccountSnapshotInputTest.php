<?php

declare(strict_types=1);

namespace Tests\Account\Account\Application\UseCase\Query\GetAccountSnapshot;

use PHPUnit\Framework\TestCase;
use Source\Account\Account\Application\UseCase\Query\GetAccountSnapshot\GetAccountSnapshotInput;
use Source\Account\Account\Domain\ValueObject\AccountSnapshotIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Tests\Helper\CreateAccountSnapshot;
use Tests\Helper\StrTestHelper;

class GetAccountSnapshotInputTest extends TestCase
{
    public function testPreservesTypedInputInstances(): void
    {
        $id = new AccountIdentifier(StrTestHelper::generateUuid());
        $p = CreateAccountSnapshot::principal($id);
        $s = new AccountSnapshotIdentifier(StrTestHelper::generateUuid());
        $input = new GetAccountSnapshotInput($id, $p, $s);
        self::assertSame($id, $input->accountIdentifier());
        self::assertSame($p, $input->principal());
        self::assertSame($s, $input->snapshotIdentifier());
        self::assertNull((new GetAccountSnapshotInput($id, $p))->snapshotIdentifier());
    }
}
