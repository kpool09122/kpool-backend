<?php

declare(strict_types=1);

namespace Source\Account\Affiliation\Application\UseCase\Query\ListAffiliations;

interface ListAffiliationsInterface
{
    public function process(ListAffiliationsInputPort $input, ListAffiliationsOutputPort $output): void;
}
