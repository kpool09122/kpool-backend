<?php

declare(strict_types=1);

namespace Source\Account\Invitation\Application\EventHandler;

use Source\Account\Invitation\Application\Exception\InvitationEmailMismatchException;
use Source\Account\Invitation\Application\Exception\InvitationNotFoundException;
use Source\Account\Invitation\Domain\Event\InvitationAccepted;
use Source\Account\Invitation\Domain\Repository\InvitationRepositoryInterface;
use Source\Account\Principal\Domain\Factory\PrincipalFactoryInterface;
use Source\Account\Principal\Domain\Factory\PrincipalGroupFactoryInterface;
use Source\Account\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Account\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Identity\Domain\Event\IdentityCreatedViaInvitation;
use Source\Identity\Domain\Repository\IdentityRepositoryInterface;
use Source\Shared\Application\Service\Event\EventDispatcherInterface;

readonly class IdentityCreatedViaInvitationHandler
{
    private const string DEFAULT_GROUP_NAME = 'Default';

    public function __construct(
        private InvitationRepositoryInterface $invitationRepository,
        private PrincipalGroupRepositoryInterface $principalGroupRepository,
        private PrincipalGroupFactoryInterface $principalGroupFactory,
        private PrincipalFactoryInterface $principalFactory,
        private PrincipalRepositoryInterface $principalRepository,
        private IdentityRepositoryInterface $identityRepository,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function handle(IdentityCreatedViaInvitation $event): void
    {
        $invitation = $this->invitationRepository->findByToken($event->oneTimeToken);

        if ($invitation === null) {
            throw new InvitationNotFoundException('招待が見つかりません。');
        }

        $invitation->assertAcceptable();
        $identity = $this->identityRepository->findById($event->identityIdentifier);

        if ($identity === null || (string) $identity->email() !== (string) $invitation->email()) {
            throw new InvitationEmailMismatchException('招待されたメールアドレスと登録メールアドレスが一致しません。');
        }

        $defaultGroup = $this->principalGroupRepository->findDefaultByAccountId($invitation->accountIdentifier());

        if ($defaultGroup === null) {
            $defaultGroup = $this->principalGroupFactory->create(
                $invitation->accountIdentifier(),
                self::DEFAULT_GROUP_NAME,
                true,
            );
        }

        $principal = $this->principalFactory->create(
            $event->identityIdentifier,
            $invitation->accountIdentifier(),
        );
        $this->principalRepository->save($principal);

        $defaultGroup->addMember($principal->principalIdentifier());
        $this->principalGroupRepository->save($defaultGroup);

        $invitation->accept($event->identityIdentifier);
        $this->invitationRepository->save($invitation);

        $this->eventDispatcher->dispatch(new InvitationAccepted(
            invitationIdentifier: $invitation->invitationIdentifier(),
            accountIdentifier: $invitation->accountIdentifier(),
            acceptedByIdentityIdentifier: $event->identityIdentifier,
            acceptedAt: $invitation->acceptedAt(),
        ));
    }
}
