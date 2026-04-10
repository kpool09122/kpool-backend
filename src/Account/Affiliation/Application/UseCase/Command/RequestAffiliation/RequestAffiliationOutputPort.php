<?php

declare(strict_types=1);

namespace Source\Account\Affiliation\Application\UseCase\Command\RequestAffiliation;

use Source\Account\Affiliation\Domain\Entity\Affiliation;

interface RequestAffiliationOutputPort
{
    public function setAffiliation(Affiliation $affiliation): void;
}
