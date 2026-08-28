<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Application\UseCase\Query\GetContactDetail;

interface GetContactDetailInterface
{
    public function process(GetContactDetailInput $input, GetContactDetailOutput $output): void;
}
