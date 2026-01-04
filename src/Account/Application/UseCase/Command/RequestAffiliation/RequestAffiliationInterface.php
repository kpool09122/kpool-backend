<?php

declare(strict_types=1);

namespace Source\Account\Application\UseCase\Command\RequestAffiliation;

use Source\Account\Domain\Entity\AccountAffiliation;

interface RequestAffiliationInterface
{
    public function process(RequestAffiliationInputPort $input): AccountAffiliation;
}
