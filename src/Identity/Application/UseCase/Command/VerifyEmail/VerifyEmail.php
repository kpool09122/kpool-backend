<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\VerifyEmail;

use DateTimeImmutable;
use Source\Identity\Application\Service\AuthCodeSessionStorageServiceInterface;
use Source\Identity\Domain\Exception\AuthCodeSessionNotFoundException;
use Source\Identity\Domain\ValueObject\AuthCodeSession;

readonly class VerifyEmail implements VerifyEmailInterface
{
    public function __construct(
        private AuthCodeSessionStorageServiceInterface $authCodeSessionStorageService,
    ) {
    }

    /**
     * @throws AuthCodeSessionNotFoundException
     */
    public function process(VerifyEmailInputPort $input, VerifyEmailOutputPort $output): void
    {
        $session = $this->authCodeSessionStorageService->findByEmail($input->email());

        if ($session === null) {
            throw new AuthCodeSessionNotFoundException();
        }

        $now = new DateTimeImmutable('now');

        $session->checkNotExpired($now);
        $session->matchAuthCode($input->authCode());

        $verifiedSession = new AuthCodeSession(
            $input->email(),
            $input->authCode(),
            $now,
            $now,
        );

        $this->authCodeSessionStorageService->delete($input->email());
        $this->authCodeSessionStorageService->save($verifiedSession);

        $output->setSession($verifiedSession);
    }
}
