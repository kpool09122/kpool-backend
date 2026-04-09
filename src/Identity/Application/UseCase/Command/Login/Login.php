<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\Login;

use Source\Identity\Domain\Exception\IdentityNotFoundException;
use Source\Identity\Domain\Exception\InvalidCredentialsException;
use Source\Identity\Domain\Repository\IdentityRepositoryInterface;
use Source\Identity\Domain\Service\AuthServiceInterface;

readonly class Login implements LoginInterface
{
    public function __construct(
        private AuthServiceInterface    $authService,
        private IdentityRepositoryInterface $identityRepository,
    ) {
    }

    /**
     * @param LoginInputPort $input
     * @param LoginOutputPort $output
     * @return void
     * @throws IdentityNotFoundException
     * @throws InvalidCredentialsException
     */
    public function process(LoginInputPort $input, LoginOutputPort $output): void
    {
        $identity = $this->identityRepository->findByEmail($input->email());
        if (! $identity) {
            throw new IdentityNotFoundException('メールアドレスまたはパスワードが正しくありません');
        }

        $identity->isEmailVerified();
        $identity->verifyPassword($input->password());

        $this->authService->login($identity);

        $output->setIdentity($identity);
    }
}
