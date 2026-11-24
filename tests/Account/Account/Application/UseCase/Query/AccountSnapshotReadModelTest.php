<?php

declare(strict_types=1);

namespace Tests\Account\Account\Application\UseCase\Query;

use PHPUnit\Framework\TestCase;
use Source\Account\Account\Application\UseCase\Query\AccountSnapshotReadModel;
use Source\Account\Account\Domain\ValueObject\DocumentType;

class AccountSnapshotReadModelTest extends TestCase
{
    public function testSerializationDoesNotExposePrivatePaths(): void
    {
        $m = new AccountSnapshotReadModel('snapshot', 'account', 'approved', 'time', true, ['email' => 'test@example.com'], [['documentType' => 'passport','documentPath' => 'secret.pdf','uploadedAt' => 'uploaded']]);
        self::assertSame(['snapshotIdentifier' => 'snapshot','accountIdentifier' => 'account','status' => 'approved','savedAt' => 'time','isCurrent' => true,'account' => ['email' => 'test@example.com'],'documents' => [['documentType' => 'passport','uploadedAt' => 'uploaded']]], $m->toArray());
        self::assertSame('secret.pdf', (string)$m->documentPath(DocumentType::PASSPORT));
        self::assertNull($m->documentPath(DocumentType::SELFIE));
    }
}
