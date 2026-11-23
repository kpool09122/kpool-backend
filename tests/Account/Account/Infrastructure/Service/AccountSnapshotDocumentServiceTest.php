<?php

declare(strict_types=1);

namespace Tests\Account\Account\Infrastructure\Service;

use DateTimeImmutable;
use Illuminate\Support\Facades\Storage;
use Psr\Log\NullLogger;
use Source\Account\Account\Application\Exception\DocumentStorageFailedException;
use Source\Account\Account\Application\Service\DocumentStorageServiceInterface;
use Source\Account\Account\Domain\ValueObject\AccountDocument;
use Source\Account\Account\Domain\ValueObject\AccountSnapshotIdentifier;
use Source\Account\Account\Domain\ValueObject\DocumentPath;
use Source\Account\Account\Domain\ValueObject\DocumentType;
use Source\Account\Account\Infrastructure\Service\AccountSnapshotDocumentService;
use Source\Account\Account\Infrastructure\Service\DocumentStorageService;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class AccountSnapshotDocumentServiceTest extends TestCase
{
    public function testReadFailureAfterCopyCleansDestinationEvenWithoutTransaction(): void
    {
        $storage = $this->createMock(DocumentStorageServiceInterface::class);
        $destination = null;
        $storage->expects(self::once())->method('copy')->willReturnCallback(function (DocumentPath $source, DocumentPath $target) use (&$destination): void {
            $destination = $target;
        });
        $storage->expects(self::once())->method('get')->willReturn(null);
        $storage->expects(self::once())->method('delete')->willReturnCallback(function (DocumentPath $path) use (&$destination): void {
            self::assertSame($destination, $path);
        });
        $accountId = new AccountIdentifier(StrTestHelper::generateUuid());
        $document = new AccountDocument($accountId, DocumentType::PASSPORT, new DocumentPath('first.pdf'), new DateTimeImmutable());
        $this->expectException(DocumentStorageFailedException::class);
        (new AccountSnapshotDocumentService($storage))->capture($accountId, new AccountSnapshotIdentifier(StrTestHelper::generateUuid()), [$document]);
    }

    public function testContentIdentityIgnoresPathAndUploadTimeAndCopiesIndependentBytes(): void
    {
        $disk = Storage::fake('verification-documents');
        $accountId = new AccountIdentifier(StrTestHelper::generateUuid());
        $disk->put('first.pdf', 'same bytes');
        $disk->put('second.pdf', 'same bytes');
        $first = new AccountDocument($accountId, DocumentType::PASSPORT, new DocumentPath('first.pdf'), new DateTimeImmutable('2026-01-01'));
        $second = new AccountDocument($accountId, DocumentType::PASSPORT, new DocumentPath('second.pdf'), new DateTimeImmutable('2026-02-01'));
        $service = new AccountSnapshotDocumentService(new DocumentStorageService(new NullLogger()));
        self::assertSame($service->hashes([$first]), $service->hashes([$second]));
        $copied = $service->capture($accountId, new AccountSnapshotIdentifier(StrTestHelper::generateUuid()), [$first]);
        $disk->put('first.pdf', 'changed');
        self::assertSame('same bytes', $disk->get((string) $copied[0]->documentPath()));
        self::assertNotSame($service->hashes([$first]), $service->hashes([$second]));
    }
}
