<?php

declare(strict_types=1);

namespace Source\Account\Delegation\Application\UseCase\Query\ListDelegations;

interface ListDelegationsInterface
{
    public function process(ListDelegationsInputPort $input, ListDelegationsOutputPort $output): void;
}
