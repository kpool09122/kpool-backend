<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\Login;

use DomainException;
use Source\Identity\Domain\Entity\Identity;
use Source\Identity\Domain\Exception\IdentityNotFoundException;
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
     * @return Identity
     * @throws IdentityNotFoundException
     * @throws DomainException
     */
    public function process(LoginInputPort $input): Identity
    {
        $identity = $this->identityRepository->findByEmail($input->email());
        if (! $identity) {
            throw new IdentityNotFoundException('メールアドレスまたはパスワードが正しくありません');
        }

        $identity->isEmailVerified();
        $identity->verifyPassword($input->password());

        $this->authService->login($identity);

        return $identity;
    }
}
