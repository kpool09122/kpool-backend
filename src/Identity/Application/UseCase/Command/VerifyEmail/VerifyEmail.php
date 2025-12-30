<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\VerifyEmail;

use DateTimeImmutable;
use Source\Identity\Domain\Entity\AuthCodeSession;
use Source\Identity\Domain\Exception\AuthCodeSessionNotFoundException;
use Source\Identity\Domain\Factory\AuthCodeSessionFactoryInterface;
use Source\Identity\Domain\Repository\AuthCodeSessionRepositoryInterface;

readonly class VerifyEmail implements VerifyEmailInterface
{
    public function __construct(
        private AuthCodeSessionRepositoryInterface $authCodeSessionRepository,
        private AuthCodeSessionFactoryInterface $authCodeSessionFactory,
    ) {
    }

    /**
     * @param VerifyEmailInputPort $input
     * @return AuthCodeSession
     * @throws AuthCodeSessionNotFoundException
     */
    public function process(VerifyEmailInputPort $input): AuthCodeSession
    {
        $session = $this->authCodeSessionRepository->findByEmail($input->email());

        if (! $session) {
            throw new AuthCodeSessionNotFoundException();
        }

        $now = new DateTimeImmutable('now');

        $session->checkNotExpired($now);
        $session->matchAuthCode($input->authCode());

        $verifiedSession = $this->authCodeSessionFactory->create(
            $input->email(),
            $input->authCode(),
            $now,
        );

        $this->authCodeSessionRepository->delete($input->email());
        $this->authCodeSessionRepository->save($verifiedSession);

        return $verifiedSession;
    }
}
