<?php

declare(strict_types=1);

namespace Tests\Wiki\OfficialCertification\Infrastructure\Repository;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\OfficialCertification\Domain\Entity\OfficialCertification;
use Source\Wiki\OfficialCertification\Domain\Repository\OfficialCertificationRepositoryInterface;
use Source\Wiki\OfficialCertification\Domain\ValueObject\CertificationIdentifier;
use Source\Wiki\OfficialCertification\Domain\ValueObject\CertificationStatus;
use Source\Wiki\OfficialCertification\Infrastructure\Repository\OfficialCertificationRepository;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class OfficialCertificationRepositoryTest extends TestCase
{
    /**
     * 正常系: DIが正しく動作すること.
     *
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $repository = $this->app->make(OfficialCertificationRepositoryInterface::class);
        $this->assertInstanceOf(OfficialCertificationRepository::class, $repository);
    }

    /**
     * 正常系: 正しくOfficialCertificationを保存できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSave(): void
    {
        $certificationId = StrTestHelper::generateUuid();
        $translationSetIdentifier = StrTestHelper::generateUuid();
        $ownerAccountId = StrTestHelper::generateUuid();

        $certification = new OfficialCertification(
            new CertificationIdentifier($certificationId),
            ResourceType::AGENCY,
            new TranslationSetIdentifier($translationSetIdentifier),
            new AccountIdentifier($ownerAccountId),
            CertificationStatus::PENDING,
            new DateTimeImmutable('2024-01-01 00:00:00'),
            null,
            null,
        );

        $repository = $this->app->make(OfficialCertificationRepositoryInterface::class);
        $repository->save($certification);

        $this->assertDatabaseHas('official_certifications', [
            'id' => $certificationId,
            'resource_type' => ResourceType::AGENCY->value,
            'translation_set_identifier' => $translationSetIdentifier,
            'owner_account_id' => $ownerAccountId,
            'status' => CertificationStatus::PENDING->value,
        ]);
    }

    /**
     * 正常系: RejectedのOfficialCertificationが存在しても同じリソースのPendingを保存できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSavePendingWhenRejectedCertificationExistsForSameResource(): void
    {
        $translationSetIdentifier = StrTestHelper::generateUuid();
        $ownerAccountId = StrTestHelper::generateUuid();
        $requestedAt = '2024-01-01 00:00:00';

        DB::table('official_certifications')->insert([
            'id' => StrTestHelper::generateUuid(),
            'resource_type' => ResourceType::AGENCY->value,
            'translation_set_identifier' => $translationSetIdentifier,
            'owner_account_id' => $ownerAccountId,
            'status' => CertificationStatus::REJECTED->value,
            'requested_at' => $requestedAt,
            'approved_at' => null,
            'rejected_at' => '2024-01-02 00:00:00',
            'created_at' => $requestedAt,
            'updated_at' => $requestedAt,
        ]);

        $pendingCertificationId = StrTestHelper::generateUuid();
        $certification = new OfficialCertification(
            new CertificationIdentifier($pendingCertificationId),
            ResourceType::AGENCY,
            new TranslationSetIdentifier($translationSetIdentifier),
            new AccountIdentifier(StrTestHelper::generateUuid()),
            CertificationStatus::PENDING,
            new DateTimeImmutable('2024-01-03 00:00:00'),
            null,
            null,
        );

        $repository = $this->app->make(OfficialCertificationRepositoryInterface::class);
        $repository->save($certification);

        $this->assertDatabaseHas('official_certifications', [
            'id' => $pendingCertificationId,
            'resource_type' => ResourceType::AGENCY->value,
            'translation_set_identifier' => $translationSetIdentifier,
            'status' => CertificationStatus::PENDING->value,
        ]);
    }

    /**
     * 異常系: 同じリソースのPendingは複数保存できないこと.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSavePendingWhenPendingCertificationExistsForSameResourceThrows(): void
    {
        $translationSetIdentifier = StrTestHelper::generateUuid();
        $ownerAccountId = StrTestHelper::generateUuid();
        $requestedAt = '2024-01-01 00:00:00';

        DB::table('official_certifications')->insert([
            'id' => StrTestHelper::generateUuid(),
            'resource_type' => ResourceType::AGENCY->value,
            'translation_set_identifier' => $translationSetIdentifier,
            'owner_account_id' => $ownerAccountId,
            'status' => CertificationStatus::PENDING->value,
            'requested_at' => $requestedAt,
            'approved_at' => null,
            'rejected_at' => null,
            'created_at' => $requestedAt,
            'updated_at' => $requestedAt,
        ]);

        $certification = new OfficialCertification(
            new CertificationIdentifier(StrTestHelper::generateUuid()),
            ResourceType::AGENCY,
            new TranslationSetIdentifier($translationSetIdentifier),
            new AccountIdentifier(StrTestHelper::generateUuid()),
            CertificationStatus::PENDING,
            new DateTimeImmutable('2024-01-03 00:00:00'),
            null,
            null,
        );

        $repository = $this->app->make(OfficialCertificationRepositoryInterface::class);

        $this->expectException(QueryException::class);

        $repository->save($certification);
    }

    /**
     * 正常系: 正しくIDに紐づくOfficialCertificationを取得できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindById(): void
    {
        $certificationId = StrTestHelper::generateUuid();
        $translationSetIdentifier = StrTestHelper::generateUuid();
        $ownerAccountId = StrTestHelper::generateUuid();
        $requestedAt = '2024-02-01 10:00:00';

        DB::table('official_certifications')->insert([
            'id' => $certificationId,
            'resource_type' => ResourceType::GROUP->value,
            'translation_set_identifier' => $translationSetIdentifier,
            'owner_account_id' => $ownerAccountId,
            'status' => CertificationStatus::APPROVED->value,
            'requested_at' => $requestedAt,
            'approved_at' => '2024-02-02 10:00:00',
            'rejected_at' => null,
            'created_at' => $requestedAt,
            'updated_at' => $requestedAt,
        ]);

        $repository = $this->app->make(OfficialCertificationRepositoryInterface::class);
        $result = $repository->findById(new CertificationIdentifier($certificationId));

        $this->assertNotNull($result);
        $this->assertSame($certificationId, (string) $result->certificationIdentifier());
        $this->assertSame(ResourceType::GROUP, $result->resourceType());
        $this->assertSame($translationSetIdentifier, (string) $result->translationSetIdentifier());
        $this->assertSame($ownerAccountId, (string) $result->ownerAccountIdentifier());
        $this->assertTrue($result->status()->isApproved());
        $this->assertSame($requestedAt, $result->requestedAt()->format('Y-m-d H:i:s'));
        $this->assertSame('2024-02-02 10:00:00', $result->approvedAt()?->format('Y-m-d H:i:s'));
    }

    /**
     * 正常系: 指定したIDを持つOfficialCertificationが存在しない場合、NULLが返却されること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByIdWhenNotFound(): void
    {
        $repository = $this->app->make(OfficialCertificationRepositoryInterface::class);
        $result = $repository->findById(new CertificationIdentifier(StrTestHelper::generateUuid()));

        $this->assertNull($result);
    }

    /**
     * 正常系: リソース指定でOfficialCertificationを取得できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByResource(): void
    {
        $certificationId = StrTestHelper::generateUuid();
        $translationSetIdentifier = StrTestHelper::generateUuid();
        $ownerAccountId = StrTestHelper::generateUuid();
        $requestedAt = '2024-03-01 12:00:00';

        DB::table('official_certifications')->insert([
            'id' => $certificationId,
            'resource_type' => ResourceType::SONG->value,
            'translation_set_identifier' => $translationSetIdentifier,
            'owner_account_id' => $ownerAccountId,
            'status' => CertificationStatus::PENDING->value,
            'requested_at' => $requestedAt,
            'approved_at' => null,
            'rejected_at' => null,
            'created_at' => $requestedAt,
            'updated_at' => $requestedAt,
        ]);

        $repository = $this->app->make(OfficialCertificationRepositoryInterface::class);
        $result = $repository->findByResource(ResourceType::SONG, new TranslationSetIdentifier($translationSetIdentifier));

        $this->assertNotNull($result);
        $this->assertSame($certificationId, (string) $result->certificationIdentifier());
        $this->assertSame(ResourceType::SONG, $result->resourceType());
        $this->assertSame($translationSetIdentifier, (string) $result->translationSetIdentifier());
    }

    /**
     * 正常系: 指定したリソースに紐づくOfficialCertificationが存在しない場合、NULLが返却されること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByResourceWhenNotFound(): void
    {
        $repository = $this->app->make(OfficialCertificationRepositoryInterface::class);
        $result = $repository->findByResource(
            ResourceType::GROUP,
            new TranslationSetIdentifier(StrTestHelper::generateUuid())
        );

        $this->assertNull($result);
    }

    /**
     * 正常系: rejected_at が存在する場合に正しく取得できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByIdWithRejectedAt(): void
    {
        $certificationId = StrTestHelper::generateUuid();
        $translationSetIdentifier = StrTestHelper::generateUuid();
        $ownerAccountId = StrTestHelper::generateUuid();
        $requestedAt = '2024-06-01 09:00:00';
        $rejectedAt = '2024-06-02 09:00:00';

        DB::table('official_certifications')->insert([
            'id' => $certificationId,
            'resource_type' => ResourceType::AGENCY->value,
            'translation_set_identifier' => $translationSetIdentifier,
            'owner_account_id' => $ownerAccountId,
            'status' => CertificationStatus::REJECTED->value,
            'requested_at' => $requestedAt,
            'approved_at' => null,
            'rejected_at' => $rejectedAt,
            'created_at' => $requestedAt,
            'updated_at' => $requestedAt,
        ]);

        $repository = $this->app->make(OfficialCertificationRepositoryInterface::class);
        $result = $repository->findById(new CertificationIdentifier($certificationId));

        $this->assertNotNull($result);
        $this->assertTrue($result->status()->isRejected());
        $this->assertSame($rejectedAt, $result->rejectedAt()?->format('Y-m-d H:i:s'));
    }

    /**
     * 正常系: リソースとステータス指定でOfficialCertificationを取得できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByResourceAndStatus(): void
    {
        $certificationId = StrTestHelper::generateUuid();
        $translationSetIdentifier = StrTestHelper::generateUuid();
        $ownerAccountId = StrTestHelper::generateUuid();
        $requestedAt = '2024-04-01 08:00:00';

        DB::table('official_certifications')->insert([
            'id' => $certificationId,
            'resource_type' => ResourceType::TALENT->value,
            'translation_set_identifier' => $translationSetIdentifier,
            'owner_account_id' => $ownerAccountId,
            'status' => CertificationStatus::PENDING->value,
            'requested_at' => $requestedAt,
            'approved_at' => null,
            'rejected_at' => null,
            'created_at' => $requestedAt,
            'updated_at' => $requestedAt,
        ]);

        $repository = $this->app->make(OfficialCertificationRepositoryInterface::class);
        $result = $repository->findByResourceAndStatus(
            ResourceType::TALENT,
            new TranslationSetIdentifier($translationSetIdentifier),
            CertificationStatus::PENDING,
        );

        $this->assertNotNull($result);
        $this->assertSame($certificationId, (string) $result->certificationIdentifier());
    }

    /**
     * 正常系: リソースが一致してもステータスが異なる場合はNULLが返却されること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByResourceAndStatusWhenStatusDoesNotMatch(): void
    {
        $translationSetIdentifier = StrTestHelper::generateUuid();
        $ownerAccountId = StrTestHelper::generateUuid();
        $requestedAt = '2024-05-01 08:00:00';

        DB::table('official_certifications')->insert([
            'id' => StrTestHelper::generateUuid(),
            'resource_type' => ResourceType::AGENCY->value,
            'translation_set_identifier' => $translationSetIdentifier,
            'owner_account_id' => $ownerAccountId,
            'status' => CertificationStatus::REJECTED->value,
            'requested_at' => $requestedAt,
            'approved_at' => null,
            'rejected_at' => $requestedAt,
            'created_at' => $requestedAt,
            'updated_at' => $requestedAt,
        ]);

        $repository = $this->app->make(OfficialCertificationRepositoryInterface::class);
        $result = $repository->findByResourceAndStatus(
            ResourceType::AGENCY,
            new TranslationSetIdentifier($translationSetIdentifier),
            CertificationStatus::PENDING,
        );

        $this->assertNull($result);
    }
}
