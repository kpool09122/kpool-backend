<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\SendAuthCode;

use Source\Identity\Domain\Factory\AuthCodeSessionFactoryInterface;
use Source\Identity\Domain\Repository\AuthCodeSessionRepositoryInterface;
use Source\Identity\Domain\Repository\IdentityRepositoryInterface;
use Source\Identity\Domain\Service\AuthCodeServiceInterface;

readonly class SendAuthCode implements SendAuthCodeInterface
{
    public function __construct(
        private AuthCodeServiceInterface $authCodeService,
        private IdentityRepositoryInterface  $identityRepository,
        private AuthCodeSessionFactoryInterface $authCodeSessionFactory,
        private AuthCodeSessionRepositoryInterface $authCodeSessionRepository,
    ) {
    }

    public function process(SendAuthCodeInputPort $input): void
    {
        $email = $input->email();
        $identity = $this->identityRepository->findByEmail($email);

        if ($identity !== null) {
            $this->authCodeService->notifyConflict($email, $input->language());

            return;
        }

        $code = $this->authCodeService->generateCode($email);
        $session = $this->authCodeSessionFactory->create($email, $code);
        $this->authCodeSessionRepository->save($session);
        $this->authCodeService->send($email, $input->language(), $session);
    }
}
