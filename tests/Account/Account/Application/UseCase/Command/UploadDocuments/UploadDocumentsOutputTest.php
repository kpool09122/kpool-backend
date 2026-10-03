<?php

declare(strict_types=1);

namespace Tests\Account\Account\Application\UseCase\Command\UploadDocuments;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Account\Account\Application\UseCase\Command\UploadDocuments\UploadDocumentsOutput;
use Source\Account\Account\Domain\ValueObject\AccountDocument;
use Source\Account\Account\Domain\ValueObject\DocumentPath;
use Source\Account\Account\Domain\ValueObject\DocumentType;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

class UploadDocumentsOutputTest extends TestCase
{
    public function testSerializesSuppliedEntity(): void
    {
        $output = new UploadDocumentsOutput();
        $entity = new AccountDocument(new AccountIdentifier('019c9b4c-0000-7000-8000-000000000001'), DocumentType::RESIDENT_REGISTRATION, new DocumentPath('documents/file.pdf'), new DateTimeImmutable('2026-10-03T01:02:03+00:00'));
        $output->setDocuments([$entity]);
        $this->assertSame(['documents' => [[
            'documentType' => DocumentType::RESIDENT_REGISTRATION->value,
            'documentPath' => (string) new DocumentPath('documents/file.pdf'),
            'uploadedAt' => '2026-10-03T01:02:03+00:00',
        ]]], $output->toArray());
    }

    public function testEmptyOutput(): void
    {
        $this->assertSame(['documents' => []], (new UploadDocumentsOutput())->toArray());
    }
}
