<?php

declare(strict_types=1);

namespace Source\Account\Affiliation\Application\UseCase\Command\RejectAffiliation;

interface RejectAffiliationInterface
{
    public function process(RejectAffiliationInputPort $input): void;
}
