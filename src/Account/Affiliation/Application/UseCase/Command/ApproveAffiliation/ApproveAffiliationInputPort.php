<?php

declare(strict_types=1);

namespace Source\Account\Affiliation\Application\UseCase\Command\ApproveAffiliation;

use Source\Account\Shared\Domain\ValueObject\AffiliationIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface ApproveAffiliationInputPort
{
    public function affiliationIdentifier(): AffiliationIdentifier;

    public function approverAccountIdentifier(): AccountIdentifier;
}
