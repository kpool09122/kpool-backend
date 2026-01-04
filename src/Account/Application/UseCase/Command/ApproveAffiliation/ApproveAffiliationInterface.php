<?php

declare(strict_types=1);

namespace Source\Account\Application\UseCase\Command\ApproveAffiliation;

use Source\Account\Domain\Entity\AccountAffiliation;

interface ApproveAffiliationInterface
{
    public function process(ApproveAffiliationInputPort $input): AccountAffiliation;
}
