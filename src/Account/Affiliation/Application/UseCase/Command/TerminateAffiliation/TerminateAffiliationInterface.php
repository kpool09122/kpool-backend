<?php

declare(strict_types=1);

namespace Source\Account\Affiliation\Application\UseCase\Command\TerminateAffiliation;

interface TerminateAffiliationInterface
{
    public function process(TerminateAffiliationInputPort $input, TerminateAffiliationOutputPort $output): void;
}
