<?php

declare(strict_types=1);

namespace Tests\Account\Account\Application\UseCase\Command\SaveAccountSnapshot;

use PHPUnit\Framework\TestCase;
use Source\Account\Account\Application\UseCase\Command\SaveAccountSnapshot\SaveAccountSnapshotInput;
use Source\Account\Account\Domain\ValueObject\AccountSnapshotIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Tests\Helper\CreateAccountSnapshot;
use Tests\Helper\StrTestHelper;

class SaveAccountSnapshotInputTest extends TestCase
{
    public function testPreservesTypedInputInstances(): void
    {
        $id = new AccountIdentifier(StrTestHelper::generateUuid());
        $p = CreateAccountSnapshot::principal($id);
        $s = new AccountSnapshotIdentifier(StrTestHelper::generateUuid());
        $input = new SaveAccountSnapshotInput($id, $p);
        self::assertSame($id, $input->accountIdentifier());
        self::assertSame($p, $input->principal());

    }
}
