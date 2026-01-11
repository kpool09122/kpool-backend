<?php

declare(strict_types=1);

namespace Tests\Wiki\OfficialCertification\Domain\Entity;

use DateTimeImmutable;
use DomainException;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Wiki\OfficialCertification\Domain\Entity\OfficialCertification;
use Source\Wiki\OfficialCertification\Domain\ValueObject\CertificationIdentifier;
use Source\Wiki\OfficialCertification\Domain\ValueObject\CertificationStatus;
use Source\Wiki\OfficialCertification\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class OfficialCertificationTest extends TestCase
{
    /**
     * 正常系: 正しくインスタンスを作成できること.
     *
     * @return void
     */
    public function test__construct(): void
    {
        $certificationIdentifier = new CertificationIdentifier(StrTestHelper::generateUuid());
        $resourceType = ResourceType::AGENCY;
        $resourceIdentifier = new ResourceIdentifier(StrTestHelper::generateUuid());
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $certificationStatus = CertificationStatus::REJECTED;
        $requestedAt = new DateTimeImmutable();
        $approvedAt = new DateTimeImmutable();
        $rejectedAt = new DateTimeImmutable();
        $certification = new OfficialCertification(
            $certificationIdentifier,
            $resourceType,
            $resourceIdentifier,
            $accountIdentifier,
            $certificationStatus,
            $requestedAt,
            $approvedAt,
            $rejectedAt,
        );
        $this->assertSame($certificationIdentifier, $certification->certificationIdentifier());
        $this->assertSame($resourceType, $certification->resourceType());
        $this->assertSame($resourceIdentifier, $certification->resourceIdentifier());
        $this->assertSame($accountIdentifier, $certification->ownerAccountIdentifier());
        $this->assertSame($certificationStatus, $certification->status());
        $this->assertSame($requestedAt, $certification->requestedAt());
        $this->assertSame($approvedAt, $certification->approvedAt());
        $this->assertSame($rejectedAt, $certification->rejectedAt());
    }

    /**
     * 正常系: 正しくApproveできること.
     *
     * @return void
     */
    public function testApprove(): void
    {
        $certification = $this->createPendingCertification();

        $certification->approve();

        $this->assertTrue($certification->isApproved());
        $this->assertNotNull($certification->approvedAt());
    }

    /**
     * 正常系: 正しくRejectできること.
     *
     * @return void
     */
    public function testReject(): void
    {
        $certification = $this->createPendingCertification();

        $certification->reject();

        $this->assertTrue($certification->isRejected());
        $this->assertNotNull($certification->rejectedAt());
    }

    /**
     * 異常系: Pending以外のステータスの時にApproveできないこと.
     *
     * @return void
     */
    public function testApproveWhenNotPendingThrows(): void
    {
        $certification = new OfficialCertification(
            new CertificationIdentifier(StrTestHelper::generateUuid()),
            ResourceType::AGENCY,
            new ResourceIdentifier(StrTestHelper::generateUuid()),
            new AccountIdentifier(StrTestHelper::generateUuid()),
            CertificationStatus::APPROVED,
            new DateTimeImmutable(),
            new DateTimeImmutable(),
            null,
        );

        $this->expectException(DomainException::class);

        $certification->approve();
    }

    /**
     * 異常系: Pending以外のステータスの時にRejectできないこと.
     *
     * @return void
     */
    public function testRejectWhenNotPendingThrows(): void
    {
        $certification = new OfficialCertification(
            new CertificationIdentifier(StrTestHelper::generateUuid()),
            ResourceType::AGENCY,
            new ResourceIdentifier(StrTestHelper::generateUuid()),
            new AccountIdentifier(StrTestHelper::generateUuid()),
            CertificationStatus::REJECTED,
            new DateTimeImmutable(),
            new DateTimeImmutable(),
            null,
        );

        $this->expectException(DomainException::class);

        $certification->reject();
    }

    private function createPendingCertification(): OfficialCertification
    {
        return new OfficialCertification(
            new CertificationIdentifier(StrTestHelper::generateUuid()),
            ResourceType::GROUP,
            new ResourceIdentifier(StrTestHelper::generateUuid()),
            new AccountIdentifier(StrTestHelper::generateUuid()),
            CertificationStatus::PENDING,
            new DateTimeImmutable(),
            null,
            null,
        );
    }
}
