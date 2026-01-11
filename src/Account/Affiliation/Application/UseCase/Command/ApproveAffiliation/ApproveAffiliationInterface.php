<?php

declare(strict_types=1);

namespace Source\Account\Affiliation\Application\UseCase\Command\ApproveAffiliation;

use Source\Account\Affiliation\Domain\Entity\Affiliation;

interface ApproveAffiliationInterface
{
    public function process(ApproveAffiliationInputPort $input): Affiliation;
}
