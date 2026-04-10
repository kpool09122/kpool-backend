<?php

declare(strict_types=1);

namespace Source\Account\Affiliation\Application\UseCase\Command\ApproveAffiliation;

interface ApproveAffiliationInterface
{
    public function process(ApproveAffiliationInputPort $input, ApproveAffiliationOutputPort $output): void;
}
