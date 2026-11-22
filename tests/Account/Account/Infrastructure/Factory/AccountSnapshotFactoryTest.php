<?php

declare(strict_types=1);

namespace Tests\Account\Account\Infrastructure\Factory;

use PHPUnit\Framework\TestCase;
use Source\Account\Account\Infrastructure\Factory\AccountSnapshotFactory;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Tests\Helper\CreateAccountSnapshotProfile;
use Tests\Helper\StrTestHelper;

class AccountSnapshotFactoryTest extends TestCase
{
    public function testCreatesIndependentVersionsWithNewIdentifiers(): void
    {
        $uuid = $this->createMock(UuidGeneratorInterface::class);
        $first = StrTestHelper::generateUuid();
        $second = StrTestHelper::generateUuid();
        $uuid->expects(self::exactly(2))->method('generate')->willReturnOnConsecutiveCalls($first, $second);
        $f = new AccountSnapshotFactory($uuid);
        $id = $f->createIdentifier();
        $aid = new AccountIdentifier(StrTestHelper::generateUuid());
        $a = CreateAccountSnapshotProfile::create();
        $s = $f->create($id, $aid, 2, $a, []);
        self::assertSame($first, (string)$s->snapshotIdentifier());
        self::assertSame($aid, $s->accountIdentifier());
        self::assertSame($a, $s->account());
        self::assertSame(2, $s->version());
        self::assertSame([], $s->documents());
        self::assertSame($second, (string)$f->createIdentifier());
    }
}
