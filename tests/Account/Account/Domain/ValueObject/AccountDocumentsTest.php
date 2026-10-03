<?php

declare(strict_types=1);

namespace Tests\Account\Account\Domain\ValueObject;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Account\Account\Domain\ValueObject\AccountDocument;
use Source\Account\Account\Domain\ValueObject\AccountDocuments;
use Source\Account\Account\Domain\ValueObject\DocumentPath;
use Source\Account\Account\Domain\ValueObject\DocumentType;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

class AccountDocumentsTest extends TestCase
{
    public function testReplacesDocumentOfSameTypeAndRetainsOtherTypes(): void
    {
        $original = $this->document(DocumentType::BUSINESS_REGISTRATION, 'original.pdf');
        $replacement = $this->document(DocumentType::BUSINESS_REGISTRATION, 'replacement.pdf');
        $other = $this->document(DocumentType::PASSPORT, 'identity.pdf');
        $documents = new AccountDocuments([$original, $other]);
        $documents->add($replacement);
        $this->assertSame([$replacement, $other], $documents->all());
        $this->assertSame([DocumentType::BUSINESS_REGISTRATION, DocumentType::PASSPORT], $documents->documentTypes());
        $documents->replaceWith([$original]);
        $this->assertSame([$original], $documents->all());
        $documents->replaceWith([]);
        $this->assertSame([], $documents->all());
        $this->assertSame([], $documents->documentTypes());
    }

    private function document(DocumentType $documentType, string $path): AccountDocument
    {
        return new AccountDocument(new AccountIdentifier('019c9b4c-0000-7000-8000-000000000001'), $documentType, new DocumentPath($path), new DateTimeImmutable('2026-10-03'));
    }
}
