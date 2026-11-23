<?php

declare(strict_types=1);

namespace Tests\Account\Account\Domain\ValueObject;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Account\Account\Domain\ValueObject\AccountSnapshotDocument;
use Source\Account\Account\Domain\ValueObject\DocumentPath;
use Source\Account\Account\Domain\ValueObject\DocumentType;

class AccountSnapshotDocumentTest extends TestCase
{
    public function testPreservesTypePathTimeAndContentIdentity(): void
    {
        $p = new DocumentPath('snapshot.pdf');
        $t = new DateTimeImmutable('2026-01-01');
        $d = new AccountSnapshotDocument(DocumentType::PASSPORT, $p, $t, 'fingerprint');
        self::assertSame(DocumentType::PASSPORT, $d->documentType());
        self::assertSame($p, $d->documentPath());
        self::assertSame($t, $d->uploadedAt());
        self::assertSame('fingerprint', $d->contentHash());
    }
}
