<?php

declare(strict_types=1);

namespace Source\Account\Invitation\Application\EventHandler;

use Source\Account\IdentityGroup\Domain\Factory\IdentityGroupFactoryInterface;
use Source\Account\IdentityGroup\Domain\Repository\IdentityGroupRepositoryInterface;
use Source\Account\IdentityGroup\Domain\ValueObject\AccountRole;
use Source\Account\Invitation\Application\Exception\InvitationNotFoundException;
use Source\Account\Invitation\Domain\Event\InvitationAccepted;
use Source\Account\Invitation\Domain\Repository\InvitationRepositoryInterface;
use Source\Identity\Domain\Event\IdentityCreatedViaInvitation;

readonly class IdentityCreatedViaInvitationHandler
{
    private const string MEMBER_GROUP_NAME = 'Members';

    public function __construct(
        private InvitationRepositoryInterface $invitationRepository,
        private IdentityGroupRepositoryInterface $identityGroupRepository,
        private IdentityGroupFactoryInterface $identityGroupFactory,
    ) {
    }

    public function handle(IdentityCreatedViaInvitation $event): void
    {
        $invitation = $this->invitationRepository->findByToken($event->invitationToken);

        if ($invitation === null) {
            throw new InvitationNotFoundException('招待が見つかりません。');
        }

        $invitation->assertAcceptable();

        $memberGroup = $this->identityGroupRepository->findByAccountIdAndRole(
            $invitation->accountIdentifier(),
            AccountRole::MEMBER
        );

        if ($memberGroup === null) {
            $memberGroup = $this->identityGroupFactory->create(
                $invitation->accountIdentifier(),
                self::MEMBER_GROUP_NAME,
                AccountRole::MEMBER,
                false,
            );
        }

        $memberGroup->addMember($event->identityIdentifier);
        $this->identityGroupRepository->save($memberGroup);

        $invitation->accept($event->identityIdentifier);
        $this->invitationRepository->save($invitation);

        event(new InvitationAccepted(
            invitationIdentifier: $invitation->invitationIdentifier(),
            accountIdentifier: $invitation->accountIdentifier(),
            acceptedByIdentityIdentifier: $event->identityIdentifier,
            acceptedAt: $invitation->acceptedAt(),
        ));
    }
}
