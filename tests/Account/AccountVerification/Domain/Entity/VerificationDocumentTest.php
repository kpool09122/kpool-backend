<?php

declare(strict_types=1);

namespace Tests\Account\AccountVerification\Domain\Entity;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Account\AccountVerification\Domain\Entity\VerificationDocument;
use Source\Account\AccountVerification\Domain\ValueObject\DocumentIdentifier;
use Source\Account\AccountVerification\Domain\ValueObject\DocumentPath;
use Source\Account\AccountVerification\Domain\ValueObject\DocumentType;
use Source\Account\AccountVerification\Domain\ValueObject\VerificationIdentifier;
use Tests\Helper\StrTestHelper;

class VerificationDocumentTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成され、各getterが正しく値を返すこと.
     *
     * @return void
     */
    public function test__construct(): void
    {
        $testData = $this->createDummyDocument();
        $document = $testData->document;

        $this->assertSame((string) $testData->documentIdentifier, (string) $document->documentIdentifier());
        $this->assertSame((string) $testData->verificationIdentifier, (string) $document->verificationIdentifier());
        $this->assertSame($testData->documentType, $document->documentType());
        $this->assertSame((string) $testData->documentPath, (string) $document->documentPath());
        $this->assertSame($testData->originalFileName, $document->originalFileName());
        $this->assertSame($testData->fileSizeBytes, $document->fileSizeBytes());
        $this->assertSame($testData->uploadedAt, $document->uploadedAt());
    }

    /**
     * ダミーのVerificationDocumentを作成するヘルパーメソッド.
     *
     * @return VerificationDocumentTestData
     */
    private function createDummyDocument(): VerificationDocumentTestData
    {
        $documentIdentifier = new DocumentIdentifier(StrTestHelper::generateUuid());
        $verificationIdentifier = new VerificationIdentifier(StrTestHelper::generateUuid());
        $documentType = DocumentType::PASSPORT;
        $documentPath = new DocumentPath('/verifications/documents/test-file.jpg');
        $originalFileName = 'my-passport.jpg';
        $fileSizeBytes = 1024000;
        $uploadedAt = new DateTimeImmutable();

        $document = new VerificationDocument(
            $documentIdentifier,
            $verificationIdentifier,
            $documentType,
            $documentPath,
            $originalFileName,
            $fileSizeBytes,
            $uploadedAt,
        );

        return new VerificationDocumentTestData(
            documentIdentifier: $documentIdentifier,
            verificationIdentifier: $verificationIdentifier,
            documentType: $documentType,
            documentPath: $documentPath,
            originalFileName: $originalFileName,
            fileSizeBytes: $fileSizeBytes,
            uploadedAt: $uploadedAt,
            document: $document,
        );
    }
}

/**
 * テストデータを保持するクラス.
 */
readonly class VerificationDocumentTestData
{
    /**
     * テストデータなので、すべてpublicで定義.
     */
    public function __construct(
        public DocumentIdentifier $documentIdentifier,
        public VerificationIdentifier $verificationIdentifier,
        public DocumentType $documentType,
        public DocumentPath $documentPath,
        public string $originalFileName,
        public int $fileSizeBytes,
        public DateTimeImmutable $uploadedAt,
        public VerificationDocument $document,
    ) {
    }
}
