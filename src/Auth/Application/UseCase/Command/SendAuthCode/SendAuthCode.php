<?php

declare(strict_types=1);

namespace Source\Auth\Application\UseCase\Command\SendAuthCode;

use Source\Auth\Domain\Repository\UserRepositoryInterface;
use Source\Auth\Domain\Service\AuthCodeServiceInterface;

readonly class SendAuthCode implements SendAuthCodeInterface
{
    public function __construct(
        private AuthCodeServiceInterface $authCodeService,
        private UserRepositoryInterface  $userRepository,
    ) {
    }

    public function process(SendAuthCodeInputPort $input): void
    {
        $email = $input->email();
        $user = $this->userRepository->findByEmail($email);

        if ($user !== null) {
            $this->authCodeService->notifyConflict($email);

            return;
        }

        $session = $this->authCodeService->generateSession($email);
        $this->authCodeService->send($session);
    }
}
