<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\Logout;

use Source\Identity\Domain\Service\AuthServiceInterface;

readonly class Logout implements LogoutInterface
{
    public function __construct(
        private AuthServiceInterface    $authService,
    ) {
    }

    /**
     * @param LogoutInputPort $input
     * @return void
     */
    public function process(LogoutInputPort $input): void
    {
        if (! $this->authService->isLoggedIn()) {
            return;
        }

        $this->authService->logout();
    }
}
