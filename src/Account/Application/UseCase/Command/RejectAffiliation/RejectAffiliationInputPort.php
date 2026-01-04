<?php

declare(strict_types=1);

namespace Source\Account\Application\UseCase\Command\RejectAffiliation;

use Source\Account\Domain\ValueObject\AffiliationIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface RejectAffiliationInputPort
{
    public function affiliationIdentifier(): AffiliationIdentifier;

    public function rejectorAccountIdentifier(): AccountIdentifier;
}
