<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Application\UseCase\Query\GetMyContactDetail;

interface GetMyContactDetailInterface
{
    public function process(GetMyContactDetailInput $input, GetMyContactDetailOutput $output): void;
}
