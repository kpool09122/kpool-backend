<?php

declare(strict_types=1);

namespace Source\Account\Affiliation\Application\UseCase\Command\RequestAffiliation;

interface RequestAffiliationInterface
{
    public function process(RequestAffiliationInputPort $input, RequestAffiliationOutputPort $output): void;
}
