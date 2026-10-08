<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Application\UseCase\Query\ListContactsByPrincipal;

use Throwable;

interface ListContactsByPrincipalInterface
{
    /** @throws Throwable */
    public function process(ListContactsByPrincipalInputPort $input, ListContactsByPrincipalOutputPort $output): void;
}
