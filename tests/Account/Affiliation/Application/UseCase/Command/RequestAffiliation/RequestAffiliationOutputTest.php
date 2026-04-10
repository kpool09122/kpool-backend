<?php

declare(strict_types=1);

namespace Tests\Account\Affiliation\Application\UseCase\Command\RequestAffiliation;

use DateTimeImmutable;
use DateTimeInterface;
use PHPUnit\Framework\TestCase;
use Source\Account\Affiliation\Application\UseCase\Command\RequestAffiliation\RequestAffiliationOutput;
use Source\Account\Affiliation\Domain\Entity\Affiliation;
use Source\Account\Affiliation\Domain\ValueObject\AffiliationStatus;
use Source\Account\Affiliation\Domain\ValueObject\AffiliationTerms;
use Source\Account\Shared\Domain\ValueObject\AffiliationIdentifier;
use Source\Monetization\Shared\ValueObject\Percentage;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Tests\Helper\StrTestHelper;

class RequestAffiliationOutputTest extends TestCase
{
    public function testToArrayWithAffiliation(): void
    {
        $requestedAt = new DateTimeImmutable('-1 day');
        $affiliation = new Affiliation(
            new AffiliationIdentifier(StrTestHelper::generateUuid()),
            new AccountIdentifier(StrTestHelper::generateUuid()),
            new AccountIdentifier(StrTestHelper::generateUuid()),
            new AccountIdentifier(StrTestHelper::generateUuid()),
            AffiliationStatus::PENDING,
            new AffiliationTerms(new Percentage(30), 'Contract notes'),
            $requestedAt,
            null,
            null,
        );

        $output = new RequestAffiliationOutput();
        $output->setAffiliation($affiliation);

        $result = $output->toArray();

        $this->assertSame((string) $affiliation->affiliationIdentifier(), $result['affiliationIdentifier']);
        $this->assertSame((string) $affiliation->agencyAccountIdentifier(), $result['agencyAccountIdentifier']);
        $this->assertSame((string) $affiliation->talentAccountIdentifier(), $result['talentAccountIdentifier']);
        $this->assertSame((string) $affiliation->requestedBy(), $result['requestedBy']);
        $this->assertSame(AffiliationStatus::PENDING->value, $result['status']);
        $this->assertSame(30, $result['terms']['revenueSharePercentage']);
        $this->assertSame('Contract notes', $result['terms']['contractNotes']);
        $this->assertSame($requestedAt->format(DateTimeInterface::ATOM), $result['requestedAt']);
        $this->assertNull($result['activatedAt']);
        $this->assertNull($result['terminatedAt']);
    }

    public function testToArrayWithoutAffiliation(): void
    {
        $output = new RequestAffiliationOutput();

        $this->assertSame([], $output->toArray());
    }
}
