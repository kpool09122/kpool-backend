<?php

declare(strict_types=1);

namespace Tests\Account\Account\Application\UseCase\Query;

use PHPUnit\Framework\TestCase;
use Source\Account\Account\Domain\ValueObject\DocumentType;
use Tests\Helper\CreateAccountSnapshotChangeRequest;

class AccountSnapshotChangeRequestReadModelTest extends TestCase
{
    public function testMasksPathsAndHashesButRetainsAuthorizedDocumentLookup(): void
    {
        $model = CreateAccountSnapshotChangeRequest::readModel();
        self::assertSame($model->summary(), $model->toArray()['request']);
        self::assertSame('Original', $model->toArray()['original']['account']['name']);
        self::assertSame('Proposed', $model->toArray()['proposal']['account']['name']);
        self::assertSame([['documentType' => 'passport', 'uploadedAt' => '2026-01-01T00:00:00+00:00']], $model->toArray()['proposal']['documents']);
        self::assertSame('private/proposal.pdf', (string) $model->documentPath(DocumentType::PASSPORT));
        self::assertSame('private/original.pdf', (string) $model->documentPath(DocumentType::PASSPORT, true));
        self::assertNull($model->documentPath(DocumentType::SELFIE));
        self::assertStringNotContainsString('private/', json_encode($model->toArray(), JSON_THROW_ON_ERROR));
    }
}
