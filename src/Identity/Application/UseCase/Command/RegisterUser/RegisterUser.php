<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\RegisterUser;

use InvalidArgumentException;
use Source\Identity\Domain\Entity\Identity;
use Source\Identity\Domain\Exception\AlreadyUserExistsException;
use Source\Identity\Domain\Exception\AuthCodeSessionNotFoundException;
use Source\Identity\Domain\Factory\IdentityFactoryInterface;
use Source\Identity\Domain\Repository\AuthCodeSessionRepositoryInterface;
use Source\Identity\Domain\Repository\IdentityRepositoryInterface;
use Source\Shared\Application\Exception\InvalidBase64ImageException;
use Source\Shared\Application\Service\ImageServiceInterface;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;

readonly class RegisterUser implements RegisterUserInterface
{
    public function __construct(
        private AuthCodeSessionRepositoryInterface $authCodeSessionRepository,
        private IdentityFactoryInterface $identityFactory,
        private ImageServiceInterface $imageService,
        private IdentityRepositoryInterface $identityRepository,
    ) {
    }

    /**
     * @param RegisterUserInputPort $input
     * @return Identity
     * @throws AuthCodeSessionNotFoundException
     * @throws UnauthorizedException
     * @throws AlreadyUserExistsException
     * @throws InvalidBase64ImageException
     */
    public function process(RegisterUserInputPort $input): Identity
    {
        if ((string)$input->password() !== (string)$input->confirmedPassword()) {
            throw new InvalidArgumentException('パスワードが一致しません');
        }

        $session = $this->authCodeSessionRepository->findByEmail($input->email());
        if (! $session) {
            throw new AuthCodeSessionNotFoundException();
        }

        $existsIdentity = $this->identityRepository->findByEmail($input->email());
        if ($existsIdentity) {
            throw new AlreadyUserExistsException();
        }

        $identity = $this->identityFactory->create(
            $input->username(),
            $input->email(),
            $input->language(),
            $input->password(),
        );
        $identity->copyEmailVerifiedAt($session);

        if ($input->base64EncodedImage()) {
            $uploadResult = $this->imageService->upload($input->base64EncodedImage());
            $identity->setProfileImage($uploadResult->resized);
        }

        $this->identityRepository->save($identity);

        return $identity;
    }
}
