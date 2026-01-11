<?php

declare(strict_types=1);

namespace Source\Account\Affiliation\Domain\Factory;

use Source\Account\Affiliation\Domain\Entity\Affiliation;
use Source\Account\Affiliation\Domain\ValueObject\AffiliationTerms;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface AffiliationFactoryInterface
{
    public function create(
        AccountIdentifier $agencyAccountIdentifier,
        AccountIdentifier $talentAccountIdentifier,
        AccountIdentifier $requestedBy,
        ?AffiliationTerms $terms,
    ): Affiliation;
}
