<?php

declare(strict_types=1);

namespace Source\Account\Affiliation\Application\UseCase\Command\RequestAffiliation;

use Source\Account\Affiliation\Domain\ValueObject\AffiliationTerms;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface RequestAffiliationInputPort
{
    public function agencyAccountIdentifier(): AccountIdentifier;

    public function talentAccountIdentifier(): AccountIdentifier;

    public function requestedBy(): AccountIdentifier;

    public function terms(): ?AffiliationTerms;
}
