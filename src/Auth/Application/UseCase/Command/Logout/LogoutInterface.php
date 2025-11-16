<?php

declare(strict_types=1);

namespace Source\Auth\Application\UseCase\Command\Logout;

interface LogoutInterface
{
    /**
     * @param LogoutInputPort $input
     * @return void
     */
    public function process(LogoutInputPort $input): void;
}
