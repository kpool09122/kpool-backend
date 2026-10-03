<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Application\UseCase\Query\ListContactsByIdentity;

use Throwable;

interface ListContactsByIdentityInterface
{
    /** @throws Throwable */
    public function process(ListContactsByIdentityInputPort $input, ListContactsByIdentityOutputPort $output): void;
}
