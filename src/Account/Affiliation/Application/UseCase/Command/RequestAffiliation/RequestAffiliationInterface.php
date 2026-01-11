<?php

declare(strict_types=1);

namespace Source\Account\Affiliation\Application\UseCase\Command\RequestAffiliation;

use Source\Account\Affiliation\Domain\Entity\Affiliation;

interface RequestAffiliationInterface
{
    public function process(RequestAffiliationInputPort $input): Affiliation;
}
