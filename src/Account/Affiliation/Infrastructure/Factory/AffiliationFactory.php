<?php

declare(strict_types=1);

namespace Source\Account\Affiliation\Infrastructure\Factory;

use DateTimeImmutable;
use Ramsey\Uuid\Uuid;
use Source\Account\Affiliation\Domain\Entity\Affiliation;
use Source\Account\Affiliation\Domain\Factory\AffiliationFactoryInterface;
use Source\Account\Affiliation\Domain\ValueObject\AffiliationStatus;
use Source\Account\Affiliation\Domain\ValueObject\AffiliationTerms;
use Source\Account\Shared\Domain\ValueObject\AffiliationIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

readonly class AffiliationFactory implements AffiliationFactoryInterface
{
    public function create(
        AccountIdentifier $agencyAccountIdentifier,
        AccountIdentifier $talentAccountIdentifier,
        AccountIdentifier $requestedBy,
        ?AffiliationTerms $terms,
    ): Affiliation {
        return new Affiliation(
            new AffiliationIdentifier(Uuid::uuid7()->toString()),
            $agencyAccountIdentifier,
            $talentAccountIdentifier,
            $requestedBy,
            AffiliationStatus::PENDING,
            $terms,
            new DateTimeImmutable(),
            null,
            null,
        );
    }
}
