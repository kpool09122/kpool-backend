<?php

declare(strict_types=1);

namespace Tests\Account\Affiliation\Application\UseCase\Command\TerminateAffiliation;

use DateTimeImmutable;
use DateTimeInterface;
use PHPUnit\Framework\TestCase;
use Source\Account\Affiliation\Application\UseCase\Command\TerminateAffiliation\TerminateAffiliationOutput;
use Source\Account\Affiliation\Domain\Entity\Affiliation;
use Source\Account\Affiliation\Domain\ValueObject\AffiliationStatus;
use Source\Account\Affiliation\Domain\ValueObject\AffiliationTerms;
use Source\Account\Shared\Domain\ValueObject\AffiliationIdentifier;
use Source\Monetization\Shared\ValueObject\Percentage;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Tests\Helper\StrTestHelper;

class TerminateAffiliationOutputTest extends TestCase
{
    public function testToArrayWithAffiliation(): void
    {
        $requestedAt = new DateTimeImmutable('-2 days');
        $activatedAt = new DateTimeImmutable('-1 day');
        $terminatedAt = new DateTimeImmutable();
        $affiliation = new Affiliation(
            new AffiliationIdentifier(StrTestHelper::generateUuid()),
            new AccountIdentifier(StrTestHelper::generateUuid()),
            new AccountIdentifier(StrTestHelper::generateUuid()),
            new AccountIdentifier(StrTestHelper::generateUuid()),
            AffiliationStatus::TERMINATED,
            new AffiliationTerms(new Percentage(40), 'Termination notes'),
            $requestedAt,
            $activatedAt,
            $terminatedAt,
        );

        $output = new TerminateAffiliationOutput();
        $output->setAffiliation($affiliation);

        $result = $output->toArray();

        $this->assertSame(AffiliationStatus::TERMINATED->value, $result['status']);
        $this->assertSame(40, $result['terms']['revenueSharePercentage']);
        $this->assertSame('Termination notes', $result['terms']['contractNotes']);
        $this->assertSame($requestedAt->format(DateTimeInterface::ATOM), $result['requestedAt']);
        $this->assertSame($activatedAt->format(DateTimeInterface::ATOM), $result['activatedAt']);
        $this->assertSame($terminatedAt->format(DateTimeInterface::ATOM), $result['terminatedAt']);
    }

    public function testToArrayWithoutAffiliation(): void
    {
        $output = new TerminateAffiliationOutput();

        $this->assertSame([], $output->toArray());
    }
}
