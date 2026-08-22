<?php

declare(strict_types=1);

namespace Tests\Wiki\OfficialCertification\Application\UseCase\Command\RejectCertification;

use DateTimeImmutable;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\OfficialCertification\Application\UseCase\Command\RejectCertification\RejectCertificationOutput;
use Source\Wiki\OfficialCertification\Domain\Entity\OfficialCertification;
use Source\Wiki\OfficialCertification\Domain\ValueObject\CertificationIdentifier;
use Source\Wiki\OfficialCertification\Domain\ValueObject\CertificationStatus;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class RejectCertificationOutputTest extends TestCase
{
    /**
     * 正常系: OfficialCertificationがセットされるとtoArrayが正しい値を返すこと.
     */
    public function testToArrayWithOfficialCertification(): void
    {
        $certificationIdentifier = new CertificationIdentifier(StrTestHelper::generateUuid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());

        $certification = new OfficialCertification(
            $certificationIdentifier,
            ResourceType::GROUP,
            $translationSetIdentifier,
            new AccountIdentifier(StrTestHelper::generateUuid()),
            CertificationStatus::REJECTED,
            new DateTimeImmutable(),
            null,
            new DateTimeImmutable(),
        );

        $output = new RejectCertificationOutput();
        $output->setOfficialCertification($certification);

        $result = $output->toArray();

        $this->assertSame((string) $certificationIdentifier, $result['certificationIdentifier']);
        $this->assertSame(ResourceType::GROUP->value, $result['resourceType']);
        $this->assertSame((string) $translationSetIdentifier, $result['translationSetIdentifier']);
        $this->assertSame(CertificationStatus::REJECTED->value, $result['status']);
    }

    /**
     * 正常系: OfficialCertificationが未セットの場合toArrayがnull値の配列を返すこと.
     */
    public function testToArrayWithoutOfficialCertification(): void
    {
        $output = new RejectCertificationOutput();

        $result = $output->toArray();

        $this->assertNull($result['certificationIdentifier']);
        $this->assertNull($result['resourceType']);
        $this->assertNull($result['translationSetIdentifier']);
        $this->assertNull($result['status']);
    }
}
