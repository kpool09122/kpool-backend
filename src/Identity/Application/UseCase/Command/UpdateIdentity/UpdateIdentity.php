<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\UpdateIdentity;

use Source\Identity\Domain\Exception\IdentityNotFoundException;
use Source\Identity\Domain\Exception\InvalidDelegationException;
use Source\Identity\Domain\Repository\IdentityRepositoryInterface;
use Source\Identity\Domain\Service\AuthServiceInterface;
use Source\Shared\Application\Exception\InvalidBase64ImageException;
use Source\Shared\Application\Service\ImageServiceInterface;

readonly class UpdateIdentity implements UpdateIdentityInterface
{
    public function __construct(
        private IdentityRepositoryInterface $identityRepository,
        private ImageServiceInterface $imageService,
        private AuthServiceInterface $authService,
    ) {
    }

    /**
     * @throws IdentityNotFoundException
     * @throws InvalidDelegationException
     * @throws InvalidBase64ImageException
     */
    public function process(UpdateIdentityInputPort $input, UpdateIdentityOutputPort $output): void
    {
        if ($input->delegationIdentifier() !== null || $input->originalIdentityIdentifier() !== null) {
            throw new InvalidDelegationException('Delegated identity cannot update profile.');
        }

        $identity = $this->identityRepository->findById($input->identityIdentifier());
        if ($identity === null) {
            throw new IdentityNotFoundException('Identity not found.');
        }

        if ($input->identityName() !== null) {
            $identity->setIdentityName($input->identityName());
        }
        if ($input->language() !== null) {
            $identity->setLanguage($input->language());
        }
        if ($input->base64EncodedImage() !== null) {
            $uploadResult = $this->imageService->upload($input->base64EncodedImage());
            $identity->setProfileImage($uploadResult->resized);
        }

        $this->identityRepository->save($identity);
        $this->authService->refreshAuthenticatedIdentity($identity);
        $output->setIdentity($identity);
    }
}
