<?php

declare(strict_types=1);

namespace Source\Account\Affiliation\Application\UseCase\Command\TerminateAffiliation;

use Source\Account\Affiliation\Domain\Entity\Affiliation;

interface TerminateAffiliationInterface
{
    public function process(TerminateAffiliationInputPort $input): Affiliation;
}
