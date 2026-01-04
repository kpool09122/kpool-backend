<?php

declare(strict_types=1);

namespace Source\Account\Application\UseCase\Command\TerminateAffiliation;

use Source\Account\Domain\ValueObject\AffiliationIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface TerminateAffiliationInputPort
{
    public function affiliationIdentifier(): AffiliationIdentifier;

    public function terminatorAccountIdentifier(): AccountIdentifier;
}
