<?php

declare(strict_types=1);

namespace Source\Account\Affiliation\Application\UseCase\Command\RequestAffiliation;

use Source\Account\Affiliation\Domain\ValueObject\AffiliationTerms;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Shared\Domain\ValueObject\Email;

interface RequestAffiliationInputPort
{
    public function principal(): Principal;

    public function targetEmail(): Email;

    public function terms(): ?AffiliationTerms;
}
