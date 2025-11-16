<?php

declare(strict_types=1);

namespace Source\Auth\Application\UseCase\Command\VerifyEmail;

use DateTimeImmutable;
use Source\Auth\Domain\Entity\AuthCodeSession;
use Source\Auth\Domain\Exception\AuthCodeSessionNotFoundException;
use Source\Auth\Domain\Factory\AuthCodeSessionFactoryInterface;
use Source\Auth\Domain\Repository\AuthCodeSessionRepositoryInterface;

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
