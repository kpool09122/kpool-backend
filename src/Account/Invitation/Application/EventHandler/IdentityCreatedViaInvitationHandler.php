<?php

declare(strict_types=1);

namespace Source\Account\Invitation\Application\EventHandler;

use Source\Account\Invitation\Application\Exception\InvitationNotFoundException;
use Source\Account\Invitation\Domain\Event\InvitationAccepted;
use Source\Account\Invitation\Domain\Repository\InvitationRepositoryInterface;
use Source\Account\Principal\Domain\Factory\PrincipalGroupFactoryInterface;
use Source\Account\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Account\Principal\Domain\ValueObject\AccountRole;
use Source\Identity\Domain\Event\IdentityCreatedViaInvitation;
use Source\Shared\Application\Service\Event\EventDispatcherInterface;

readonly class IdentityCreatedViaInvitationHandler
{
    private const string MEMBER_GROUP_NAME = 'Members';

    public function __construct(
        private InvitationRepositoryInterface $invitationRepository,
        private PrincipalGroupRepositoryInterface $principalGroupRepository,
        private PrincipalGroupFactoryInterface $principalGroupFactory,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function handle(IdentityCreatedViaInvitation $event): void
    {
        $invitation = $this->invitationRepository->findByToken($event->invitationToken);

        if ($invitation === null) {
            throw new InvitationNotFoundException('招待が見つかりません。');
        }

        $invitation->assertAcceptable();

        $memberGroup = $this->principalGroupRepository->findByAccountIdAndRole(
            $invitation->accountIdentifier(),
            AccountRole::MEMBER
        );

        if ($memberGroup === null) {
            $memberGroup = $this->principalGroupFactory->create(
                $invitation->accountIdentifier(),
                self::MEMBER_GROUP_NAME,
                AccountRole::MEMBER,
                false,
            );
        }

        $memberGroup->addMember($event->identityIdentifier);
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
