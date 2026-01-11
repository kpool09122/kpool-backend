<?php

declare(strict_types=1);

namespace Source\Account\Affiliation\Application\UseCase\Command\RequestAffiliation;

use Source\Account\Affiliation\Domain\ValueObject\AffiliationTerms;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

readonly class RequestAffiliationInput implements RequestAffiliationInputPort
{
    public function __construct(
        private AccountIdentifier $agencyAccountIdentifier,
        private AccountIdentifier $talentAccountIdentifier,
        private AccountIdentifier $requestedBy,
        private ?AffiliationTerms $terms,
    ) {
    }

    public function agencyAccountIdentifier(): AccountIdentifier
    {
        return $this->agencyAccountIdentifier;
    }

    public function talentAccountIdentifier(): AccountIdentifier
    {
        return $this->talentAccountIdentifier;
    }

    public function requestedBy(): AccountIdentifier
    {
        return $this->requestedBy;
    }

    public function terms(): ?AffiliationTerms
    {
        return $this->terms;
    }
}
