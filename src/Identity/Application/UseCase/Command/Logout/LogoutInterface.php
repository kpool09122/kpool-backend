<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\Logout;

interface LogoutInterface
{
    /**
     * @param LogoutInputPort $input
     * @return void
     */
    public function process(LogoutInputPort $input): void;
}
