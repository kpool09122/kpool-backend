<?php

declare(strict_types=1);

namespace Source\Account\Invitation\Application\UseCase\Command\InviteMember;

use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\Account\Domain\ValueObject\AccountType;
use Source\Account\Invitation\Application\Exception\DisallowedInvitationException;
use Source\Account\Invitation\Domain\Event\InvitationCreated;
use Source\Account\Invitation\Domain\Factory\InvitationFactoryInterface;
use Source\Account\Invitation\Domain\Repository\InvitationRepositoryInterface;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Account\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Account\Principal\Domain\ValueObject\Action;
use Source\Account\Principal\Domain\ValueObject\Resource;
use Source\Shared\Application\Service\Event\EventDispatcherInterface;

readonly class InviteMember implements InviteMemberInterface
{
    public function __construct(
        private InvitationRepositoryInterface $invitationRepository,
        private InvitationFactoryInterface $invitationFactory,
        private PolicyEvaluatorInterface $policyEvaluator,
        private PrincipalRepositoryInterface $principalRepository,
        private AccountRepositoryInterface $accountRepository,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function process(InviteMemberInputPort $input, InviteMemberOutputPort $output): void
    {
        $this->assertAccountAllowsInvitation($input);
        $principal = $this->findInviterPrincipal($input);
        $this->assertInviterHasPermission($input, $principal);

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
                $principal->identityIdentifier(),
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

        $output->setInvitations($invitations, $principal->principalIdentifier());
    }

    private function findInviterPrincipal(InviteMemberInputPort $input): Principal
    {
        $principal = $this->principalRepository->findById($input->inviterPrincipalIdentifier());

        if ($principal !== null && (string) $principal->accountIdentifier() === (string) $input->accountIdentifier()) {
            return $principal;
        }

        throw new DisallowedInvitationException('招待を作成する権限がありません。');
    }

    private function assertInviterHasPermission(
        InviteMemberInputPort $input,
        Principal $principal,
    ): void {
        $allowed = $this->policyEvaluator->evaluate(
            $principal,
            Action::INVITE_MEMBER,
            Resource::account($input->accountIdentifier()),
        );

        if ($allowed) {
            return;
        }

        throw new DisallowedInvitationException('招待を作成する権限がありません。');
    }

    private function assertAccountAllowsInvitation(InviteMemberInputPort $input): void
    {
        $account = $this->accountRepository->findById($input->accountIdentifier());

        if ($account?->type() === AccountType::CORPORATION) {
            return;
        }

        throw new DisallowedInvitationException('法人アカウントのみメンバーを招待できます。');
    }
}
