<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Application\UseCase\Query\ListMyContacts;

use Throwable;

interface ListMyContactsInterface
{
    /**
     * @throws Throwable
     */
    public function process(ListMyContactsInputPort $input, ListMyContactsOutputPort $output): void;
}
