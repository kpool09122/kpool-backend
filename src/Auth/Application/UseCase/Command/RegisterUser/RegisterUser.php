<?php

declare(strict_types=1);

namespace Source\Auth\Application\UseCase\Command\RegisterUser;

use InvalidArgumentException;
use Source\Auth\Domain\Entity\User;
use Source\Auth\Domain\Exception\AlreadyUserExistsException;
use Source\Auth\Domain\Exception\AuthCodeSessionNotFoundException;
use Source\Auth\Domain\Factory\UserFactoryInterface;
use Source\Auth\Domain\Repository\AuthCodeSessionRepositoryInterface;
use Source\Auth\Domain\Repository\UserRepositoryInterface;
use Source\Shared\Application\Service\ImageServiceInterface;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;

readonly class RegisterUser implements RegisterUserInterface
{
    public function __construct(
        private AuthCodeSessionRepositoryInterface $authCodeSessionRepository,
        private UserFactoryInterface $userFactory,
        private ImageServiceInterface $imageService,
        private UserRepositoryInterface $userRepository,
    ) {
    }

    /**
     * @param RegisterUserInputPort $input
     * @return User
     * @throws AuthCodeSessionNotFoundException
     * @throws UnauthorizedException
     * @throws AlreadyUserExistsException
     */
    public function process(RegisterUserInputPort $input): User
    {
        if ((string)$input->password() !== (string)$input->confirmedPassword()) {
            throw new InvalidArgumentException('パスワードが一致しません');
        }

        $session = $this->authCodeSessionRepository->findByEmail($input->email());
        if (! $session) {
            throw new AuthCodeSessionNotFoundException();
        }

        $existsUser = $this->userRepository->findByEmail($input->email());
        if ($existsUser) {
            throw new AlreadyUserExistsException();
        }

        $user = $this->userFactory->create(
            $input->username(),
            $input->email(),
            $input->translation(),
            $input->password(),
        );
        $user->copyEmailVerifiedAt($session);

        if ($input->base64EncodedImage()) {
            $imagePath = $this->imageService->upload($input->base64EncodedImage());
            $user->setProfileImage($imagePath);
        }

        $this->userRepository->save($user);

        return $user;
    }
}
