<?php

declare(strict_types=1);

namespace Source\Account\Domain\Factory;

use Source\Account\Domain\Entity\AccountAffiliation;
use Source\Account\Domain\ValueObject\AffiliationTerms;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface AffiliationFactoryInterface
{
    public function create(
        AccountIdentifier $agencyAccountIdentifier,
        AccountIdentifier $talentAccountIdentifier,
        AccountIdentifier $requestedBy,
        ?AffiliationTerms $terms,
    ): AccountAffiliation;
}
