<?php

declare(strict_types=1);

namespace Source\Auth\Application\UseCase\Command\Login;

use DomainException;
use Source\Auth\Domain\Entity\User;
use Source\Auth\Domain\Exception\UserNotFoundException;
use Source\Auth\Domain\Repository\UserRepositoryInterface;
use Source\Auth\Domain\Service\AuthServiceInterface;

readonly class Login implements LoginInterface
{
    public function __construct(
        private AuthServiceInterface    $authService,
        private UserRepositoryInterface $userRepository,
    ) {
    }

    /**
     * @param LoginInputPort $input
     * @return User
     * @throws UserNotFoundException
     * @throws DomainException
     */
    public function process(LoginInputPort $input): User
    {
        $user = $this->userRepository->findByEmail($input->email());
        if (! $user) {
            throw new UserNotFoundException('メールアドレスまたはパスワードが正しくありません');
        }

        $user->isEmailVerified();
        $user->verifyPassword($input->password());

        $this->authService->login($user);

        return $user;
    }
}
