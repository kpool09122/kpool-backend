<?php

declare(strict_types=1);

namespace Source\Auth\Application\UseCase\Command\SendAuthCode;

use Source\Auth\Domain\Factory\AuthCodeSessionFactoryInterface;
use Source\Auth\Domain\Repository\AuthCodeSessionRepositoryInterface;
use Source\Auth\Domain\Repository\UserRepositoryInterface;
use Source\Auth\Domain\Service\AuthCodeServiceInterface;

readonly class SendAuthCode implements SendAuthCodeInterface
{
    public function __construct(
        private AuthCodeServiceInterface $authCodeService,
        private UserRepositoryInterface  $userRepository,
        private AuthCodeSessionFactoryInterface $authCodeSessionFactory,
        private AuthCodeSessionRepositoryInterface $authCodeSessionRepository,
    ) {
    }

    public function process(SendAuthCodeInputPort $input): void
    {
        $email = $input->email();
        $user = $this->userRepository->findByEmail($email);

        if ($user !== null) {
            $this->authCodeService->notifyConflict($email, $input->language());

            return;
        }

        $code = $this->authCodeService->generateCode($email);
        $session = $this->authCodeSessionFactory->create($email, $code);
        $this->authCodeSessionRepository->save($session);
        $this->authCodeService->send($email, $input->language(), $session);
    }
}
