<?php

declare(strict_types=1);

namespace Source\Account\Affiliation\Application\UseCase\Command\RequestAffiliation;

use Source\Account\Affiliation\Domain\ValueObject\AffiliationTerms;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Shared\Domain\ValueObject\Email;

readonly class RequestAffiliationInput implements RequestAffiliationInputPort
{
    public function __construct(
        private Principal $principal,
        private Email $targetEmail,
        private ?AffiliationTerms $terms,
    ) {
    }

    public function principal(): Principal
    {
        return $this->principal;
    }

    public function targetEmail(): Email
    {
        return $this->targetEmail;
    }

    public function terms(): ?AffiliationTerms
    {
        return $this->terms;
    }
}
