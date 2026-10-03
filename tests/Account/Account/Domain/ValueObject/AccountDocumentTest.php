<?php

declare(strict_types=1);

namespace Tests\Account\Account\Domain\ValueObject;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Account\Account\Domain\ValueObject\AccountDocument;
use Source\Account\Account\Domain\ValueObject\DocumentPath;
use Source\Account\Account\Domain\ValueObject\DocumentType;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

class AccountDocumentTest extends TestCase
{
    public function testPreservesSuppliedValues(): void
    {
        $accountIdentifier = new AccountIdentifier('019c9b4c-0000-7000-8000-000000000001');
        $documentType = DocumentType::RESIDENT_REGISTRATION;
        $documentPath = new DocumentPath('documents/file.pdf');
        $uploadedAt = new DateTimeImmutable('2026-10-03T01:02:03+00:00');

        $subject = new AccountDocument($accountIdentifier, $documentType, $documentPath, $uploadedAt);

        $this->assertSame($accountIdentifier, $subject->accountIdentifier());
        $this->assertSame($documentType, $subject->documentType());
        $this->assertSame($documentPath, $subject->documentPath());
        $this->assertSame($uploadedAt, $subject->uploadedAt());
    }
}
