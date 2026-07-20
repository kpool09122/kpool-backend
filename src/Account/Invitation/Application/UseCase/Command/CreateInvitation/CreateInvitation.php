<?php

declare(strict_types=1);

namespace Source\Account\Invitation\Application\UseCase\Command\CreateInvitation;

use Source\Account\Invitation\Application\Exception\DisallowedInvitationException;
use Source\Account\Invitation\Domain\Event\InvitationCreated;
use Source\Account\Invitation\Domain\Factory\InvitationFactoryInterface;
use Source\Account\Invitation\Domain\Repository\InvitationRepositoryInterface;
use Source\Account\Policy\Domain\Service\PolicyEvaluatorInterface;
use Source\Account\Policy\Domain\ValueObject\AccountAction;
use Source\Account\Policy\Domain\ValueObject\AccountResource;
use Source\Shared\Application\Service\Event\EventDispatcherInterface;

readonly class CreateInvitation implements CreateInvitationInterface
{
    public function __construct(
        private InvitationRepositoryInterface $invitationRepository,
        private InvitationFactoryInterface $invitationFactory,
        private PolicyEvaluatorInterface $policyEvaluator,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function process(CreateInvitationInputPort $input, CreateInvitationOutputPort $output): void
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

            $this->eventDispatcher->dispatch(new InvitationCreated(
                invitationIdentifier: $invitation->invitationIdentifier(),
                accountIdentifier: $invitation->accountIdentifier(),
                invitedByIdentityIdentifier: $invitation->invitedByIdentityIdentifier(),
                email: $invitation->email(),
                token: $invitation->token(),
            ));

            $invitations[] = $invitation;
        }

        $output->setInvitations($invitations);
    }

    private function assertInviterHasPermission(CreateInvitationInputPort $input): void
    {
        $allowed = $this->policyEvaluator->evaluate(
            $input->inviterIdentityIdentifier(),
            AccountAction::INVITATION_CREATE,
            AccountResource::account($input->accountIdentifier()),
        );

        if ($allowed) {
            return;
        }

        throw new DisallowedInvitationException('招待を作成する権限がありません。');
    }
}
