<?php

declare(strict_types=1);

namespace Tests\Account\AccountVerification\Domain\Entity;

use DateTimeImmutable;
use DomainException;
use PHPUnit\Framework\TestCase;
use Source\Account\AccountVerification\Domain\Entity\AccountVerification;
use Source\Account\AccountVerification\Domain\Entity\VerificationDocument;
use Source\Account\AccountVerification\Domain\ValueObject\ApplicantInfo;
use Source\Account\AccountVerification\Domain\ValueObject\DocumentIdentifier;
use Source\Account\AccountVerification\Domain\ValueObject\DocumentPath;
use Source\Account\AccountVerification\Domain\ValueObject\DocumentType;
use Source\Account\AccountVerification\Domain\ValueObject\RejectionReason;
use Source\Account\AccountVerification\Domain\ValueObject\RejectionReasonCode;
use Source\Account\AccountVerification\Domain\ValueObject\VerificationIdentifier;
use Source\Account\AccountVerification\Domain\ValueObject\VerificationStatus;
use Source\Account\AccountVerification\Domain\ValueObject\VerificationType;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Tests\Helper\StrTestHelper;

class AccountVerificationTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成され、各getterが正しく値を返すこと.
     *
     * @return void
     */
    public function test__construct(): void
    {
        $testData = $this->createDummyVerification();
        $verification = $testData->verification;

        $this->assertSame((string) $testData->verificationIdentifier, (string) $verification->verificationIdentifier());
        $this->assertSame((string) $testData->accountIdentifier, (string) $verification->accountIdentifier());
        $this->assertSame($testData->verificationType, $verification->verificationType());
        $this->assertSame($testData->status, $verification->status());
        $this->assertSame($testData->applicantInfo, $verification->applicantInfo());
        $this->assertSame($testData->requestedAt, $verification->requestedAt());
        $this->assertSame($testData->reviewedBy, $verification->reviewedBy());
        $this->assertSame($testData->reviewedAt, $verification->reviewedAt());
        $this->assertSame($testData->rejectionReason, $verification->rejectionReason());
        $this->assertSame([], $verification->documents());

        $this->assertTrue($verification->isPending());
        $this->assertFalse($verification->isApproved());
        $this->assertFalse($verification->isRejected());
    }

    /**
     * 正常系: PENDING状態から承認できること.
     *
     * @return void
     */
    public function testApproveFromPending(): void
    {
        $testData = $this->createDummyVerification(VerificationStatus::PENDING);
        $verification = $testData->verification;
        $reviewerAccountId = new AccountIdentifier(StrTestHelper::generateUuid());

        $verification->approve($reviewerAccountId);

        $this->assertTrue($verification->isApproved());
        $this->assertSame((string) $reviewerAccountId, (string) $verification->reviewedBy());
        $this->assertNotNull($verification->reviewedAt());
    }

    /**
     * 異常系: APPROVED状態から承認しようとすると例外がスローされること.
     *
     * @return void
     */
    public function testCannotApproveFromApproved(): void
    {
        $testData = $this->createDummyVerification(VerificationStatus::APPROVED);
        $verification = $testData->verification;
        $reviewerAccountId = new AccountIdentifier(StrTestHelper::generateUuid());

        $this->expectException(DomainException::class);

        $verification->approve($reviewerAccountId);
    }

    /**
     * 異常系: REJECTED状態から承認しようとすると例外がスローされること.
     *
     * @return void
     */
    public function testCannotApproveFromRejected(): void
    {
        $testData = $this->createDummyVerification(VerificationStatus::REJECTED);
        $verification = $testData->verification;
        $reviewerAccountId = new AccountIdentifier(StrTestHelper::generateUuid());

        $this->expectException(DomainException::class);

        $verification->approve($reviewerAccountId);
    }

    /**
     * 正常系: PENDING状態から却下できること.
     *
     * @return void
     */
    public function testRejectFromPending(): void
    {
        $testData = $this->createDummyVerification(VerificationStatus::PENDING);
        $verification = $testData->verification;
        $reviewerAccountId = new AccountIdentifier(StrTestHelper::generateUuid());
        $rejectionReason = new RejectionReason(RejectionReasonCode::DOCUMENT_UNCLEAR, 'Image is blurry');

        $verification->reject($reviewerAccountId, $rejectionReason);

        $this->assertTrue($verification->isRejected());
        $this->assertSame((string) $reviewerAccountId, (string) $verification->reviewedBy());
        $this->assertNotNull($verification->reviewedAt());
        $this->assertSame(RejectionReasonCode::DOCUMENT_UNCLEAR, $verification->rejectionReason()->code());
    }

    /**
     * 異常系: APPROVED状態から却下しようとすると例外がスローされること.
     *
     * @return void
     */
    public function testCannotRejectFromApproved(): void
    {
        $testData = $this->createDummyVerification(VerificationStatus::APPROVED);
        $verification = $testData->verification;
        $reviewerAccountId = new AccountIdentifier(StrTestHelper::generateUuid());
        $rejectionReason = new RejectionReason(RejectionReasonCode::DOCUMENT_UNCLEAR);

        $this->expectException(DomainException::class);

        $verification->reject($reviewerAccountId, $rejectionReason);
    }

    /**
     * 異常系: REJECTED状態から却下しようとすると例外がスローされること.
     *
     * @return void
     */
    public function testCannotRejectFromRejected(): void
    {
        $testData = $this->createDummyVerification(VerificationStatus::REJECTED);
        $verification = $testData->verification;
        $reviewerAccountId = new AccountIdentifier(StrTestHelper::generateUuid());
        $rejectionReason = new RejectionReason(RejectionReasonCode::DOCUMENT_UNCLEAR);

        $this->expectException(DomainException::class);

        $verification->reject($reviewerAccountId, $rejectionReason);
    }

    /**
     * 正常系: コンストラクタでdocumentsを渡した場合、正しく取得できること.
     *
     * @return void
     */
    public function testConstructWithDocuments(): void
    {
        $verificationIdentifier = new VerificationIdentifier(StrTestHelper::generateUuid());
        $document1 = $this->createDummyDocument($verificationIdentifier);
        $document2 = $this->createDummyDocument($verificationIdentifier);

        $verification = new AccountVerification(
            $verificationIdentifier,
            new AccountIdentifier(StrTestHelper::generateUuid()),
            VerificationType::TALENT,
            VerificationStatus::PENDING,
            new ApplicantInfo('Taro Yamada'),
            new DateTimeImmutable(),
            null,
            null,
            null,
            [$document1, $document2],
        );

        $this->assertCount(2, $verification->documents());
        $this->assertSame($document1, $verification->documents()[0]);
        $this->assertSame($document2, $verification->documents()[1]);
    }

    /**
     * 正常系: addDocumentでドキュメントを追加できること.
     *
     * @return void
     */
    public function testAddDocument(): void
    {
        $testData = $this->createDummyVerification();
        $verification = $testData->verification;

        $this->assertCount(0, $verification->documents());

        $document1 = $this->createDummyDocument($testData->verificationIdentifier);
        $verification->addDocument($document1);

        $this->assertCount(1, $verification->documents());
        $this->assertSame($document1, $verification->documents()[0]);

        $document2 = $this->createDummyDocument($testData->verificationIdentifier);
        $verification->addDocument($document2);

        $this->assertCount(2, $verification->documents());
        $this->assertSame($document2, $verification->documents()[1]);
    }

    /**
     * ダミーのVerificationDocumentを作成するヘルパーメソッド.
     *
     * @param VerificationIdentifier $verificationIdentifier
     * @return VerificationDocument
     */
    private function createDummyDocument(VerificationIdentifier $verificationIdentifier): VerificationDocument
    {
        return new VerificationDocument(
            new DocumentIdentifier(StrTestHelper::generateUuid()),
            $verificationIdentifier,
            DocumentType::PASSPORT,
            new DocumentPath('/verifications/documents/test-file.jpg'),
            'my-passport.jpg',
            1024000,
            new DateTimeImmutable(),
        );
    }

    /**
     * ダミーのAccountVerificationを作成するヘルパーメソッド.
     *
     * @param VerificationStatus $status
     * @return AccountVerificationTestData
     */
    private function createDummyVerification(
        VerificationStatus $status = VerificationStatus::PENDING,
    ): AccountVerificationTestData {
        $verificationIdentifier = new VerificationIdentifier(StrTestHelper::generateUuid());
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $verificationType = VerificationType::TALENT;
        $applicantInfo = new ApplicantInfo('Taro Yamada');
        $requestedAt = new DateTimeImmutable();
        $reviewedBy = null;
        $reviewedAt = null;
        $rejectionReason = null;

        $verification = new AccountVerification(
            $verificationIdentifier,
            $accountIdentifier,
            $verificationType,
            $status,
            $applicantInfo,
            $requestedAt,
            $reviewedBy,
            $reviewedAt,
            $rejectionReason,
        );

        return new AccountVerificationTestData(
            verificationIdentifier: $verificationIdentifier,
            accountIdentifier: $accountIdentifier,
            verificationType: $verificationType,
            status: $status,
            applicantInfo: $applicantInfo,
            requestedAt: $requestedAt,
            reviewedBy: $reviewedBy,
            reviewedAt: $reviewedAt,
            rejectionReason: $rejectionReason,
            verification: $verification,
        );
    }
}

/**
 * テストデータを保持するクラス.
 */
readonly class AccountVerificationTestData
{
    /**
     * テストデータなので、すべてpublicで定義.
     */
    public function __construct(
        public VerificationIdentifier $verificationIdentifier,
        public AccountIdentifier $accountIdentifier,
        public VerificationType $verificationType,
        public VerificationStatus $status,
        public ApplicantInfo $applicantInfo,
        public DateTimeImmutable $requestedAt,
        public ?AccountIdentifier $reviewedBy,
        public ?DateTimeImmutable $reviewedAt,
        public ?RejectionReason $rejectionReason,
        public AccountVerification $verification,
    ) {
    }
}
