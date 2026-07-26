<?php

declare(strict_types=1);

namespace Source\Account\Invitation\Application\EventHandler;

use RuntimeException;
use Source\Account\Invitation\Application\Exception\InvitationNotFoundException;
use Source\Account\Invitation\Domain\Event\InvitationAccepted;
use Source\Account\Invitation\Domain\Repository\InvitationRepositoryInterface;
use Source\Account\Principal\Domain\Entity\Role;
use Source\Account\Principal\Domain\Factory\PrincipalFactoryInterface;
use Source\Account\Principal\Domain\Factory\PrincipalGroupFactoryInterface;
use Source\Account\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Account\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Account\Principal\Domain\Repository\RoleRepositoryInterface;
use Source\Identity\Domain\Event\IdentityCreatedViaInvitation;
use Source\Shared\Application\Service\Event\EventDispatcherInterface;

readonly class IdentityCreatedViaInvitationHandler
{
    private const string MEMBER_GROUP_NAME = 'Members';

    public function __construct(
        private InvitationRepositoryInterface $invitationRepository,
        private PrincipalGroupRepositoryInterface $principalGroupRepository,
        private PrincipalGroupFactoryInterface $principalGroupFactory,
        private PrincipalFactoryInterface $principalFactory,
        private PrincipalRepositoryInterface $principalRepository,
        private RoleRepositoryInterface $roleRepository,
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

        $basicRole = $this->roleRepository->findByName(Role::BASIC);
        if ($basicRole === null) {
            throw new RuntimeException('Basic account role is not found.');
        }

        $memberGroup = $this->principalGroupRepository->findByAccountIdAndRole(
            $invitation->accountIdentifier(),
            $basicRole->roleIdentifier()
        );

        if ($memberGroup === null) {
            $memberGroup = $this->principalGroupFactory->create(
                $invitation->accountIdentifier(),
                self::MEMBER_GROUP_NAME,
                false,
            );
            $memberGroup->addRole($basicRole->roleIdentifier());
        }

        $principal = $this->principalFactory->create(
            $event->identityIdentifier,
            $invitation->accountIdentifier(),
        );
        $this->principalRepository->save($principal);

        $memberGroup->addMember($principal->principalIdentifier());
        $this->principalGroupRepository->save($memberGroup);

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
