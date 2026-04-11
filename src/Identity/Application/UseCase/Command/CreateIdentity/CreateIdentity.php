<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\CreateIdentity;

use Source\Identity\Domain\Event\IdentityCreatedViaInvitation;
use Source\Identity\Domain\Exception\AlreadyUserExistsException;
use Source\Identity\Domain\Exception\AuthCodeSessionNotFoundException;
use Source\Identity\Domain\Exception\PasswordMismatchException;
use Source\Identity\Domain\Exception\UnauthorizedEmailException;
use Source\Identity\Domain\Factory\IdentityFactoryInterface;
use Source\Identity\Domain\Repository\AuthCodeSessionRepositoryInterface;
use Source\Identity\Domain\Repository\IdentityRepositoryInterface;
use Source\Shared\Application\Exception\InvalidBase64ImageException;
use Source\Shared\Application\Service\Event\EventDispatcherInterface;
use Source\Shared\Application\Service\ImageServiceInterface;

readonly class CreateIdentity implements CreateIdentityInterface
{
    public function __construct(
        private AuthCodeSessionRepositoryInterface $authCodeSessionRepository,
        private IdentityFactoryInterface $identityFactory,
        private ImageServiceInterface $imageService,
        private IdentityRepositoryInterface $identityRepository,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    /**
     * @param CreateIdentityInputPort $input
     * @param CreateIdentityOutputPort $output
     * @return void
     * @throws PasswordMismatchException
     * @throws AuthCodeSessionNotFoundException
     * @throws UnauthorizedEmailException
     * @throws AlreadyUserExistsException
     * @throws InvalidBase64ImageException
     */
    public function process(CreateIdentityInputPort $input, CreateIdentityOutputPort $output): void
    {
        if ((string)$input->password() !== (string)$input->confirmedPassword()) {
            throw new PasswordMismatchException('パスワードが一致しません');
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

        if ($input->invitationToken() !== null) {
            $this->eventDispatcher->dispatch(new IdentityCreatedViaInvitation(
                identityIdentifier: $identity->identityIdentifier(),
                invitationToken: $input->invitationToken(),
            ));
        }

        $output->setIdentity($identity);
    }
}
