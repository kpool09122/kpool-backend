<?php

declare(strict_types=1);

namespace Source\Account\Affiliation\Application\UseCase\Command\ApproveAffiliation;

use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Shared\Domain\ValueObject\AffiliationIdentifier;

interface ApproveAffiliationInputPort
{
    public function affiliationIdentifier(): AffiliationIdentifier;

    public function principal(): Principal;
}
