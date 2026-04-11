<?php

declare(strict_types=1);

namespace Tests\Wiki\OfficialCertification\Application\UseCase\Command\RequestCertification;

use DateTimeImmutable;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Wiki\OfficialCertification\Application\UseCase\Command\RequestCertification\RequestCertificationOutput;
use Source\Wiki\OfficialCertification\Domain\Entity\OfficialCertification;
use Source\Wiki\OfficialCertification\Domain\ValueObject\CertificationIdentifier;
use Source\Wiki\OfficialCertification\Domain\ValueObject\CertificationStatus;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class RequestCertificationOutputTest extends TestCase
{
    /**
     * 正常系: OfficialCertificationがセットされるとtoArrayが正しい値を返すこと.
     */
    public function testToArrayWithOfficialCertification(): void
    {
        $certificationIdentifier = new CertificationIdentifier(StrTestHelper::generateUuid());
        $wikiIdentifier = new WikiIdentifier(StrTestHelper::generateUuid());

        $certification = new OfficialCertification(
            $certificationIdentifier,
            ResourceType::TALENT,
            $wikiIdentifier,
            new AccountIdentifier(StrTestHelper::generateUuid()),
            CertificationStatus::PENDING,
            new DateTimeImmutable(),
            null,
            null,
        );

        $output = new RequestCertificationOutput();
        $output->setOfficialCertification($certification);

        $result = $output->toArray();

        $this->assertSame((string) $certificationIdentifier, $result['certificationIdentifier']);
        $this->assertSame(ResourceType::TALENT->value, $result['resourceType']);
        $this->assertSame((string) $wikiIdentifier, $result['wikiIdentifier']);
        $this->assertSame(CertificationStatus::PENDING->value, $result['status']);
    }

    /**
     * 正常系: OfficialCertificationが未セットの場合toArrayがnull値の配列を返すこと.
     */
    public function testToArrayWithoutOfficialCertification(): void
    {
        $output = new RequestCertificationOutput();

        $result = $output->toArray();

        $this->assertNull($result['certificationIdentifier']);
        $this->assertNull($result['resourceType']);
        $this->assertNull($result['wikiIdentifier']);
        $this->assertNull($result['status']);
    }
}
