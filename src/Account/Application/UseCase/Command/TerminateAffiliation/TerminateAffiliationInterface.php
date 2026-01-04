<?php

declare(strict_types=1);

namespace Source\Account\Application\UseCase\Command\TerminateAffiliation;

use Source\Account\Domain\Entity\AccountAffiliation;

interface TerminateAffiliationInterface
{
    public function process(TerminateAffiliationInputPort $input): AccountAffiliation;
}
