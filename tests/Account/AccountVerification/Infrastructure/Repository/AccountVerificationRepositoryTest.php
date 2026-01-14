<?php

declare(strict_types=1);

namespace Tests\Account\AccountVerification\Infrastructure\Repository;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use PHPUnit\Framework\Attributes\Group;
use Source\Account\AccountVerification\Domain\Entity\AccountVerification;
use Source\Account\AccountVerification\Domain\Entity\VerificationDocument;
use Source\Account\AccountVerification\Domain\Repository\AccountVerificationRepositoryInterface;
use Source\Account\AccountVerification\Domain\ValueObject\ApplicantInfo;
use Source\Account\AccountVerification\Domain\ValueObject\DocumentIdentifier;
use Source\Account\AccountVerification\Domain\ValueObject\DocumentPath;
use Source\Account\AccountVerification\Domain\ValueObject\DocumentType;
use Source\Account\AccountVerification\Domain\ValueObject\RejectionReason;
use Source\Account\AccountVerification\Domain\ValueObject\RejectionReasonCode;
use Source\Account\AccountVerification\Domain\ValueObject\VerificationIdentifier;
use Source\Account\AccountVerification\Domain\ValueObject\VerificationStatus;
use Source\Account\AccountVerification\Domain\ValueObject\VerificationType;
use Source\Account\AccountVerification\Infrastructure\Repository\AccountVerificationRepository;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Tests\Helper\CreateAccountVerification;
use Tests\Helper\CreateVerificationDocument;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class AccountVerificationRepositoryTest extends TestCase
{
    /**
     * @param VerificationDocument[] $documents
     */
    private function createTestVerification(
        ?string $verificationId = null,
        ?string $accountId = null,
        VerificationType $verificationType = VerificationType::TALENT,
        VerificationStatus $status = VerificationStatus::PENDING,
        ?string $reviewedBy = null,
        ?DateTimeImmutable $reviewedAt = null,
        ?RejectionReason $rejectionReason = null,
        array $documents = [],
    ): AccountVerification {
        $verificationId ??= StrTestHelper::generateUuid();
        $accountId ??= StrTestHelper::generateUuid();

        return new AccountVerification(
            new VerificationIdentifier($verificationId),
            new AccountIdentifier($accountId),
            $verificationType,
            $status,
            new ApplicantInfo('Test User', 'Test Company', 'Test Representative'),
            new DateTimeImmutable(),
            $reviewedBy !== null ? new AccountIdentifier($reviewedBy) : null,
            $reviewedAt,
            $rejectionReason,
            $documents,
        );
    }

    private function createTestDocument(
        string $verificationId,
        ?string $documentId = null,
        DocumentType $documentType = DocumentType::PASSPORT,
    ): VerificationDocument {
        $documentId ??= StrTestHelper::generateUuid();

        return new VerificationDocument(
            new DocumentIdentifier($documentId),
            new VerificationIdentifier($verificationId),
            $documentType,
            new DocumentPath('/verification/documents/test.jpg'),
            'test.jpg',
            1024,
            new DateTimeImmutable(),
        );
    }

    /**
     * 正常系: DIが正しく動作すること.
     *
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $repository = $this->app->make(AccountVerificationRepositoryInterface::class);
        $this->assertInstanceOf(AccountVerificationRepository::class, $repository);
    }

    /**
     * 正常系: 正しく新規のAccountVerificationを保存できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSave(): void
    {
        $verificationId = StrTestHelper::generateUuid();
        $accountId = StrTestHelper::generateUuid();
        $verification = $this->createTestVerification(
            verificationId: $verificationId,
            accountId: $accountId,
        );

        $repository = $this->app->make(AccountVerificationRepositoryInterface::class);
        $repository->save($verification);

        $this->assertDatabaseHas('account_verifications', [
            'id' => $verificationId,
            'account_id' => $accountId,
            'verification_type' => 'talent',
            'status' => 'pending',
        ]);
    }

    /**
     * 正常系: ドキュメント付きでAccountVerificationを保存できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveWithDocuments(): void
    {
        $verificationId = StrTestHelper::generateUuid();
        $documentId = StrTestHelper::generateUuid();

        $document = $this->createTestDocument($verificationId, $documentId);
        $verification = $this->createTestVerification(
            verificationId: $verificationId,
            documents: [$document],
        );

        $repository = $this->app->make(AccountVerificationRepositoryInterface::class);
        $repository->save($verification);

        $this->assertDatabaseHas('account_verifications', [
            'id' => $verificationId,
        ]);

        $this->assertDatabaseHas('verification_documents', [
            'id' => $documentId,
            'verification_id' => $verificationId,
            'document_type' => 'passport',
            'document_path' => '/verification/documents/test.jpg',
            'original_file_name' => 'test.jpg',
            'file_size_bytes' => 1024,
        ]);
    }

    /**
     * 正常系: 正しくIDに紐づくAccountVerificationを取得できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindById(): void
    {
        $verificationId = StrTestHelper::generateUuid();
        $accountId = StrTestHelper::generateUuid();

        CreateAccountVerification::create(
            new VerificationIdentifier($verificationId),
            new AccountIdentifier($accountId),
            [
                'verification_type' => 'talent',
                'status' => 'pending',
                'applicant_info' => [
                    'full_name' => 'Test User',
                    'company_name' => 'Test Company',
                    'representative_name' => 'Test Representative',
                ],
            ],
        );

        $repository = $this->app->make(AccountVerificationRepositoryInterface::class);
        $result = $repository->findById(new VerificationIdentifier($verificationId));

        $this->assertNotNull($result);
        $this->assertSame($verificationId, (string) $result->verificationIdentifier());
        $this->assertSame($accountId, (string) $result->accountIdentifier());
        $this->assertSame(VerificationType::TALENT, $result->verificationType());
        $this->assertSame(VerificationStatus::PENDING, $result->status());
        $this->assertSame('Test User', $result->applicantInfo()->fullName());
        $this->assertSame('Test Company', $result->applicantInfo()->companyName());
        $this->assertSame('Test Representative', $result->applicantInfo()->representativeName());
    }

    /**
     * 正常系: ドキュメント付きでfindByIdが正しく動作すること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByIdWithDocuments(): void
    {
        $verificationId = StrTestHelper::generateUuid();
        $accountId = StrTestHelper::generateUuid();
        $documentId = StrTestHelper::generateUuid();

        CreateAccountVerification::create(
            new VerificationIdentifier($verificationId),
            new AccountIdentifier($accountId),
        );

        CreateVerificationDocument::create(
            new DocumentIdentifier($documentId),
            new VerificationIdentifier($verificationId),
            [
                'document_type' => 'passport',
                'document_path' => '/verification/documents/test.jpg',
                'original_file_name' => 'test.jpg',
                'file_size_bytes' => 1024,
            ],
        );

        $repository = $this->app->make(AccountVerificationRepositoryInterface::class);
        $result = $repository->findById(new VerificationIdentifier($verificationId));

        $this->assertNotNull($result);
        $this->assertCount(1, $result->documents());

        $resultDocument = $result->documents()[0];
        $this->assertSame($documentId, (string) $resultDocument->documentIdentifier());
        $this->assertSame($verificationId, (string) $resultDocument->verificationIdentifier());
        $this->assertSame(DocumentType::PASSPORT, $resultDocument->documentType());
        $this->assertSame('/verification/documents/test.jpg', (string) $resultDocument->documentPath());
        $this->assertSame('test.jpg', $resultDocument->originalFileName());
        $this->assertSame(1024, $resultDocument->fileSizeBytes());
    }

    /**
     * 正常系: 指定したIDを持つAccountVerificationが存在しない場合、NULLが返却されること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByIdWhenNotFound(): void
    {
        $repository = $this->app->make(AccountVerificationRepositoryInterface::class);
        $result = $repository->findById(new VerificationIdentifier(StrTestHelper::generateUuid()));

        $this->assertNull($result);
    }

    /**
     * 正常系: 正しくAccountIdに紐づくAccountVerificationを取得できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByAccountId(): void
    {
        $verificationId = StrTestHelper::generateUuid();
        $accountId = StrTestHelper::generateUuid();

        CreateAccountVerification::create(
            new VerificationIdentifier($verificationId),
            new AccountIdentifier($accountId),
        );

        $repository = $this->app->make(AccountVerificationRepositoryInterface::class);
        $result = $repository->findByAccountId(new AccountIdentifier($accountId));

        $this->assertNotNull($result);
        $this->assertSame($verificationId, (string) $result->verificationIdentifier());
        $this->assertSame($accountId, (string) $result->accountIdentifier());
    }

    /**
     * 正常系: 指定したAccountIdを持つAccountVerificationが存在しない場合、NULLが返却されること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByAccountIdWhenNotFound(): void
    {
        $repository = $this->app->make(AccountVerificationRepositoryInterface::class);
        $result = $repository->findByAccountId(new AccountIdentifier(StrTestHelper::generateUuid()));

        $this->assertNull($result);
    }

    /**
     * 正常系: 同一AccountIdで複数のVerificationがある場合、最新のものが返却されること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByAccountIdReturnsLatest(): void
    {
        $accountId = StrTestHelper::generateUuid();
        $oldVerificationId = StrTestHelper::generateUuid();
        $newVerificationId = StrTestHelper::generateUuid();

        CreateAccountVerification::create(
            new VerificationIdentifier($oldVerificationId),
            new AccountIdentifier($accountId),
            ['created_at' => new DateTimeImmutable('2024-01-01 00:00:00')],
        );

        CreateAccountVerification::create(
            new VerificationIdentifier($newVerificationId),
            new AccountIdentifier($accountId),
            ['created_at' => new DateTimeImmutable('2024-01-02 00:00:00')],
        );

        $repository = $this->app->make(AccountVerificationRepositoryInterface::class);
        $result = $repository->findByAccountId(new AccountIdentifier($accountId));

        $this->assertNotNull($result);
        $this->assertSame($newVerificationId, (string) $result->verificationIdentifier());
    }

    /**
     * 正常系: 正しくPendingステータスのAccountVerificationを取得できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindPendingByAccountId(): void
    {
        $accountId = StrTestHelper::generateUuid();
        $verificationId = StrTestHelper::generateUuid();

        CreateAccountVerification::create(
            new VerificationIdentifier($verificationId),
            new AccountIdentifier($accountId),
            ['status' => 'pending'],
        );

        $repository = $this->app->make(AccountVerificationRepositoryInterface::class);
        $result = $repository->findPendingByAccountId(new AccountIdentifier($accountId));

        $this->assertNotNull($result);
        $this->assertSame($verificationId, (string) $result->verificationIdentifier());
        $this->assertSame(VerificationStatus::PENDING, $result->status());
    }

    /**
     * 正常系: ApprovedステータスのVerificationがある場合、findPendingByAccountIdはNULLを返すこと
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindPendingByAccountIdReturnsNullForApproved(): void
    {
        $accountId = StrTestHelper::generateUuid();
        $reviewedBy = StrTestHelper::generateUuid();

        CreateAccountVerification::create(
            new VerificationIdentifier(StrTestHelper::generateUuid()),
            new AccountIdentifier($accountId),
            [
                'status' => 'approved',
                'reviewed_by' => $reviewedBy,
                'reviewed_at' => new DateTimeImmutable(),
            ],
        );

        $repository = $this->app->make(AccountVerificationRepositoryInterface::class);
        $result = $repository->findPendingByAccountId(new AccountIdentifier($accountId));

        $this->assertNull($result);
    }

    /**
     * 正常系: Pendingステータスが存在する場合、existsPendingはtrueを返すこと
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testExistsPending(): void
    {
        $accountId = StrTestHelper::generateUuid();

        CreateAccountVerification::create(
            new VerificationIdentifier(StrTestHelper::generateUuid()),
            new AccountIdentifier($accountId),
            ['status' => 'pending'],
        );

        $repository = $this->app->make(AccountVerificationRepositoryInterface::class);
        $result = $repository->existsPending(new AccountIdentifier($accountId));

        $this->assertTrue($result);
    }

    /**
     * 正常系: Pendingステータスが存在しない場合、existsPendingはfalseを返すこと
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testExistsPendingReturnsFalseWhenNotExists(): void
    {
        $repository = $this->app->make(AccountVerificationRepositoryInterface::class);

        $result = $repository->existsPending(new AccountIdentifier(StrTestHelper::generateUuid()));

        $this->assertFalse($result);
    }

    /**
     * 正常系: ApprovedステータスのVerificationがある場合、existsPendingはfalseを返すこと
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testExistsPendingReturnsFalseForApproved(): void
    {
        $accountId = StrTestHelper::generateUuid();
        $reviewedBy = StrTestHelper::generateUuid();

        CreateAccountVerification::create(
            new VerificationIdentifier(StrTestHelper::generateUuid()),
            new AccountIdentifier($accountId),
            [
                'status' => 'approved',
                'reviewed_by' => $reviewedBy,
                'reviewed_at' => new DateTimeImmutable(),
            ],
        );

        $repository = $this->app->make(AccountVerificationRepositoryInterface::class);
        $result = $repository->existsPending(new AccountIdentifier($accountId));

        $this->assertFalse($result);
    }

    /**
     * 正常系: 正しくステータスに紐づくAccountVerificationを取得できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByStatus(): void
    {
        $reviewedBy = StrTestHelper::generateUuid();

        // Pendingステータスのverificationを2件作成
        CreateAccountVerification::create(
            new VerificationIdentifier(StrTestHelper::generateUuid()),
            new AccountIdentifier(StrTestHelper::generateUuid()),
            ['status' => 'pending'],
        );
        CreateAccountVerification::create(
            new VerificationIdentifier(StrTestHelper::generateUuid()),
            new AccountIdentifier(StrTestHelper::generateUuid()),
            ['status' => 'pending'],
        );

        // Approvedステータスのverificationを1件作成
        CreateAccountVerification::create(
            new VerificationIdentifier(StrTestHelper::generateUuid()),
            new AccountIdentifier(StrTestHelper::generateUuid()),
            [
                'status' => 'approved',
                'reviewed_by' => $reviewedBy,
                'reviewed_at' => new DateTimeImmutable(),
            ],
        );

        $repository = $this->app->make(AccountVerificationRepositoryInterface::class);
        $pendingResults = $repository->findByStatus(VerificationStatus::PENDING);
        $approvedResults = $repository->findByStatus(VerificationStatus::APPROVED);

        $this->assertCount(2, $pendingResults);
        $this->assertCount(1, $approvedResults);

        foreach ($pendingResults as $result) {
            $this->assertSame(VerificationStatus::PENDING, $result->status());
        }

        foreach ($approvedResults as $result) {
            $this->assertSame(VerificationStatus::APPROVED, $result->status());
        }
    }

    /**
     * 正常系: findByStatusでlimitが正しく機能すること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByStatusWithLimit(): void
    {
        // Pendingステータスのverificationを3件作成
        CreateAccountVerification::create(
            new VerificationIdentifier(StrTestHelper::generateUuid()),
            new AccountIdentifier(StrTestHelper::generateUuid()),
            ['status' => 'pending'],
        );
        CreateAccountVerification::create(
            new VerificationIdentifier(StrTestHelper::generateUuid()),
            new AccountIdentifier(StrTestHelper::generateUuid()),
            ['status' => 'pending'],
        );
        CreateAccountVerification::create(
            new VerificationIdentifier(StrTestHelper::generateUuid()),
            new AccountIdentifier(StrTestHelper::generateUuid()),
            ['status' => 'pending'],
        );

        $repository = $this->app->make(AccountVerificationRepositoryInterface::class);
        $results = $repository->findByStatus(VerificationStatus::PENDING, limit: 2);

        $this->assertCount(2, $results);
    }

    /**
     * 正常系: findByStatusでoffsetが正しく機能すること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByStatusWithOffset(): void
    {
        // Pendingステータスのverificationを3件作成
        CreateAccountVerification::create(
            new VerificationIdentifier(StrTestHelper::generateUuid()),
            new AccountIdentifier(StrTestHelper::generateUuid()),
            ['status' => 'pending'],
        );
        CreateAccountVerification::create(
            new VerificationIdentifier(StrTestHelper::generateUuid()),
            new AccountIdentifier(StrTestHelper::generateUuid()),
            ['status' => 'pending'],
        );
        CreateAccountVerification::create(
            new VerificationIdentifier(StrTestHelper::generateUuid()),
            new AccountIdentifier(StrTestHelper::generateUuid()),
            ['status' => 'pending'],
        );

        $repository = $this->app->make(AccountVerificationRepositoryInterface::class);
        $results = $repository->findByStatus(VerificationStatus::PENDING, limit: 50, offset: 2);

        $this->assertCount(1, $results);
    }

    /**
     * 正常系: 正しく全件取得できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindAll(): void
    {
        $reviewedBy = StrTestHelper::generateUuid();

        CreateAccountVerification::create(
            new VerificationIdentifier(StrTestHelper::generateUuid()),
            new AccountIdentifier(StrTestHelper::generateUuid()),
            ['status' => 'pending'],
        );
        CreateAccountVerification::create(
            new VerificationIdentifier(StrTestHelper::generateUuid()),
            new AccountIdentifier(StrTestHelper::generateUuid()),
            [
                'status' => 'approved',
                'reviewed_by' => $reviewedBy,
                'reviewed_at' => new DateTimeImmutable(),
            ],
        );
        CreateAccountVerification::create(
            new VerificationIdentifier(StrTestHelper::generateUuid()),
            new AccountIdentifier(StrTestHelper::generateUuid()),
            [
                'status' => 'rejected',
                'reviewed_by' => $reviewedBy,
                'reviewed_at' => new DateTimeImmutable(),
                'rejection_reason' => [
                    'code' => 'document_unclear',
                    'detail' => null,
                ],
            ],
        );

        $repository = $this->app->make(AccountVerificationRepositoryInterface::class);
        $results = $repository->findAll();

        $this->assertCount(3, $results);
    }

    /**
     * 正常系: findAllでlimitが正しく機能すること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindAllWithLimit(): void
    {
        CreateAccountVerification::create(
            new VerificationIdentifier(StrTestHelper::generateUuid()),
            new AccountIdentifier(StrTestHelper::generateUuid()),
        );
        CreateAccountVerification::create(
            new VerificationIdentifier(StrTestHelper::generateUuid()),
            new AccountIdentifier(StrTestHelper::generateUuid()),
        );
        CreateAccountVerification::create(
            new VerificationIdentifier(StrTestHelper::generateUuid()),
            new AccountIdentifier(StrTestHelper::generateUuid()),
        );

        $repository = $this->app->make(AccountVerificationRepositoryInterface::class);
        $results = $repository->findAll(limit: 2);

        $this->assertCount(2, $results);
    }

    /**
     * 正常系: findAllでoffsetが正しく機能すること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindAllWithOffset(): void
    {
        CreateAccountVerification::create(
            new VerificationIdentifier(StrTestHelper::generateUuid()),
            new AccountIdentifier(StrTestHelper::generateUuid()),
        );
        CreateAccountVerification::create(
            new VerificationIdentifier(StrTestHelper::generateUuid()),
            new AccountIdentifier(StrTestHelper::generateUuid()),
        );
        CreateAccountVerification::create(
            new VerificationIdentifier(StrTestHelper::generateUuid()),
            new AccountIdentifier(StrTestHelper::generateUuid()),
        );

        $repository = $this->app->make(AccountVerificationRepositoryInterface::class);
        $results = $repository->findAll(limit: 50, offset: 2);

        $this->assertCount(1, $results);
    }

    /**
     * 正常系: RejectionReasonが正しく保存・取得できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testRejectionReasonPersistence(): void
    {
        $verificationId = StrTestHelper::generateUuid();
        $reviewedBy = StrTestHelper::generateUuid();

        CreateAccountVerification::create(
            new VerificationIdentifier($verificationId),
            new AccountIdentifier(StrTestHelper::generateUuid()),
            [
                'status' => 'rejected',
                'reviewed_by' => $reviewedBy,
                'reviewed_at' => new DateTimeImmutable(),
                'rejection_reason' => [
                    'code' => 'document_unclear',
                    'detail' => null,
                ],
            ],
        );

        $repository = $this->app->make(AccountVerificationRepositoryInterface::class);
        $result = $repository->findById(new VerificationIdentifier($verificationId));

        $this->assertNotNull($result);
        $this->assertNotNull($result->rejectionReason());
        $this->assertSame(RejectionReasonCode::DOCUMENT_UNCLEAR, $result->rejectionReason()->code());
        $this->assertNull($result->rejectionReason()->detail());
    }

    /**
     * 正常系: RejectionReasonがOTHERの場合、detailも正しく保存・取得できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testRejectionReasonWithDetailPersistence(): void
    {
        $verificationId = StrTestHelper::generateUuid();
        $reviewedBy = StrTestHelper::generateUuid();

        CreateAccountVerification::create(
            new VerificationIdentifier($verificationId),
            new AccountIdentifier(StrTestHelper::generateUuid()),
            [
                'status' => 'rejected',
                'reviewed_by' => $reviewedBy,
                'reviewed_at' => new DateTimeImmutable(),
                'rejection_reason' => [
                    'code' => 'other',
                    'detail' => 'Custom rejection reason',
                ],
            ],
        );

        $repository = $this->app->make(AccountVerificationRepositoryInterface::class);
        $result = $repository->findById(new VerificationIdentifier($verificationId));

        $this->assertNotNull($result);
        $this->assertNotNull($result->rejectionReason());
        $this->assertSame(RejectionReasonCode::OTHER, $result->rejectionReason()->code());
        $this->assertSame('Custom rejection reason', $result->rejectionReason()->detail());
    }

    /**
     * 正常系: reviewedByとreviewedAtが正しく保存・取得できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testReviewInfoPersistence(): void
    {
        $verificationId = StrTestHelper::generateUuid();
        $reviewedBy = StrTestHelper::generateUuid();
        $reviewedAt = new DateTimeImmutable('2024-01-15 10:30:00');

        CreateAccountVerification::create(
            new VerificationIdentifier($verificationId),
            new AccountIdentifier(StrTestHelper::generateUuid()),
            [
                'status' => 'approved',
                'reviewed_by' => $reviewedBy,
                'reviewed_at' => $reviewedAt,
            ],
        );

        $repository = $this->app->make(AccountVerificationRepositoryInterface::class);
        $result = $repository->findById(new VerificationIdentifier($verificationId));

        $this->assertNotNull($result);
        $this->assertNotNull($result->reviewedBy());
        $this->assertSame($reviewedBy, (string) $result->reviewedBy());
        $this->assertNotNull($result->reviewedAt());
        $this->assertSame('2024-01-15 10:30:00', $result->reviewedAt()->format('Y-m-d H:i:s'));
    }

    /**
     * 正常系: 既存のAccountVerificationを更新できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testUpdateExistingVerification(): void
    {
        $verificationId = StrTestHelper::generateUuid();
        $accountId = StrTestHelper::generateUuid();

        // ヘルパーでPendingステータスでレコード作成
        CreateAccountVerification::create(
            new VerificationIdentifier($verificationId),
            new AccountIdentifier($accountId),
            ['status' => 'pending'],
        );

        // Approvedステータスに更新
        $reviewedBy = StrTestHelper::generateUuid();
        $reviewedAt = new DateTimeImmutable();

        $updatedVerification = new AccountVerification(
            new VerificationIdentifier($verificationId),
            new AccountIdentifier($accountId),
            VerificationType::TALENT,
            VerificationStatus::APPROVED,
            new ApplicantInfo('Test User'),
            new DateTimeImmutable(),
            new AccountIdentifier($reviewedBy),
            $reviewedAt,
            null,
            [],
        );

        $repository = $this->app->make(AccountVerificationRepositoryInterface::class);
        $repository->save($updatedVerification);

        $result = $repository->findById(new VerificationIdentifier($verificationId));

        $this->assertNotNull($result);
        $this->assertSame(VerificationStatus::APPROVED, $result->status());
        $this->assertSame($reviewedBy, (string) $result->reviewedBy());
    }

    /**
     * 正常系: 複数のドキュメントを持つAccountVerificationを保存・取得できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testMultipleDocumentsPersistence(): void
    {
        $verificationId = StrTestHelper::generateUuid();
        $documentId1 = StrTestHelper::generateUuid();
        $documentId2 = StrTestHelper::generateUuid();

        CreateAccountVerification::create(
            new VerificationIdentifier($verificationId),
            new AccountIdentifier(StrTestHelper::generateUuid()),
        );

        CreateVerificationDocument::create(
            new DocumentIdentifier($documentId1),
            new VerificationIdentifier($verificationId),
            [
                'document_type' => 'passport',
                'document_path' => '/verification/documents/passport.jpg',
                'original_file_name' => 'passport.jpg',
                'file_size_bytes' => 2048,
            ],
        );

        CreateVerificationDocument::create(
            new DocumentIdentifier($documentId2),
            new VerificationIdentifier($verificationId),
            [
                'document_type' => 'selfie',
                'document_path' => '/verification/documents/selfie.jpg',
                'original_file_name' => 'selfie.jpg',
                'file_size_bytes' => 1024,
            ],
        );

        $repository = $this->app->make(AccountVerificationRepositoryInterface::class);
        $result = $repository->findById(new VerificationIdentifier($verificationId));

        $this->assertNotNull($result);
        $this->assertCount(2, $result->documents());

        $documentTypes = array_map(
            fn (VerificationDocument $doc) => $doc->documentType(),
            $result->documents(),
        );

        $this->assertContains(DocumentType::PASSPORT, $documentTypes);
        $this->assertContains(DocumentType::SELFIE, $documentTypes);
    }

    /**
     * 正常系: Agency向けのVerificationTypeで保存・取得できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testAgencyVerificationTypePersistence(): void
    {
        $verificationId = StrTestHelper::generateUuid();

        CreateAccountVerification::create(
            new VerificationIdentifier($verificationId),
            new AccountIdentifier(StrTestHelper::generateUuid()),
            ['verification_type' => 'agency'],
        );

        $repository = $this->app->make(AccountVerificationRepositoryInterface::class);
        $result = $repository->findById(new VerificationIdentifier($verificationId));

        $this->assertNotNull($result);
        $this->assertSame(VerificationType::AGENCY, $result->verificationType());
        $this->assertTrue($result->verificationType()->isAgency());
    }
}
