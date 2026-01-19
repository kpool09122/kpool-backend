<?php

declare(strict_types=1);

namespace Source\Account\Invitation\Application\UseCase\Command\CreateInvitation;

use Source\Account\IdentityGroup\Domain\Repository\IdentityGroupRepositoryInterface;
use Source\Account\IdentityGroup\Domain\ValueObject\AccountRole;
use Source\Account\Invitation\Application\Exception\DisallowedInvitationException;
use Source\Account\Invitation\Domain\Entity\Invitation;
use Source\Account\Invitation\Domain\Event\InvitationCreated;
use Source\Account\Invitation\Domain\Factory\InvitationFactoryInterface;
use Source\Account\Invitation\Domain\Repository\InvitationRepositoryInterface;

readonly class CreateInvitation implements CreateInvitationInterface
{
    public function __construct(
        private InvitationRepositoryInterface $invitationRepository,
        private InvitationFactoryInterface $invitationFactory,
        private IdentityGroupRepositoryInterface $identityGroupRepository,
    ) {
    }

    /**
     * @return array<Invitation>
     */
    public function process(CreateInvitationInputPort $input): array
    {
        $this->assertInviterHasPermission($input);

        $invitations = [];
        foreach ($input->emails() as $email) {
            $existingInvitation = $this->invitationRepository->findPendingByAccountAndEmail(
                $input->accountIdentifier(),
                $email
            );

            if ($existingInvitation !== null) {
                $existingInvitation->revoke();
                $this->invitationRepository->save($existingInvitation);
            }

            $invitation = $this->invitationFactory->create(
                $input->accountIdentifier(),
                $input->inviterIdentityIdentifier(),
                $email
            );

            $this->invitationRepository->save($invitation);

            event(new InvitationCreated(
                invitationIdentifier: $invitation->invitationIdentifier(),
                accountIdentifier: $invitation->accountIdentifier(),
                invitedByIdentityIdentifier: $invitation->invitedByIdentityIdentifier(),
                email: $invitation->email(),
                token: $invitation->token(),
            ));

            $invitations[] = $invitation;
        }

        return $invitations;
    }

    private function assertInviterHasPermission(CreateInvitationInputPort $input): void
    {
        $identityGroups = $this->identityGroupRepository->findByAccountIdAndIdentityId(
            $input->accountIdentifier(),
            $input->inviterIdentityIdentifier()
        );

        foreach ($identityGroups as $identityGroup) {
            $role = $identityGroup->role();
            if ($role === AccountRole::ADMIN || $role === AccountRole::OWNER) {
                return;
            }
        }

        throw new DisallowedInvitationException('招待を作成する権限がありません。');
    }
}
