<?php

declare(strict_types=1);

namespace Tests\Account\Affiliation\Application\UseCase\Command\ApproveAffiliation;

use DateTimeImmutable;
use DateTimeInterface;
use PHPUnit\Framework\TestCase;
use Source\Account\Affiliation\Application\UseCase\Command\ApproveAffiliation\ApproveAffiliationOutput;
use Source\Account\Affiliation\Domain\Entity\Affiliation;
use Source\Account\Affiliation\Domain\ValueObject\AffiliationStatus;
use Source\Account\Affiliation\Domain\ValueObject\AffiliationTerms;
use Source\Account\Shared\Domain\ValueObject\AffiliationIdentifier;
use Source\Monetization\Shared\ValueObject\Percentage;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Tests\Helper\StrTestHelper;

class ApproveAffiliationOutputTest extends TestCase
{
    public function testToArrayWithAffiliation(): void
    {
        $requestedAt = new DateTimeImmutable('-1 day');
        $activatedAt = new DateTimeImmutable();
        $affiliation = new Affiliation(
            new AffiliationIdentifier(StrTestHelper::generateUuid()),
            new AccountIdentifier(StrTestHelper::generateUuid()),
            new AccountIdentifier(StrTestHelper::generateUuid()),
            new AccountIdentifier(StrTestHelper::generateUuid()),
            AffiliationStatus::ACTIVE,
            new AffiliationTerms(new Percentage(35), 'Approved notes'),
            $requestedAt,
            $activatedAt,
            null,
        );

        $output = new ApproveAffiliationOutput();
        $output->setAffiliation($affiliation);

        $result = $output->toArray();

        $this->assertSame(AffiliationStatus::ACTIVE->value, $result['status']);
        $this->assertSame(35, $result['terms']['revenueSharePercentage']);
        $this->assertSame('Approved notes', $result['terms']['contractNotes']);
        $this->assertSame($requestedAt->format(DateTimeInterface::ATOM), $result['requestedAt']);
        $this->assertSame($activatedAt->format(DateTimeInterface::ATOM), $result['activatedAt']);
        $this->assertNull($result['terminatedAt']);
    }

    public function testToArrayWithoutAffiliation(): void
    {
        $output = new ApproveAffiliationOutput();

        $this->assertSame([], $output->toArray());
    }
}
