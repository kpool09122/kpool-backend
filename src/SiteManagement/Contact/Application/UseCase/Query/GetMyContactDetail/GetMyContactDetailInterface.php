<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Application\UseCase\Query\GetMyContactDetail;

interface GetMyContactDetailInterface
{
    public function process(GetMyContactDetailInputPort $input, GetMyContactDetailOutputPort $output): void;
}
