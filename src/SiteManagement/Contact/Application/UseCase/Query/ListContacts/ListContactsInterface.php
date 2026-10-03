<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Application\UseCase\Query\ListContacts;

use Throwable;

interface ListContactsInterface
{
    /** @throws Throwable */
    public function process(ListContactsInputPort $input, ListContactsOutputPort $output): void;
}
