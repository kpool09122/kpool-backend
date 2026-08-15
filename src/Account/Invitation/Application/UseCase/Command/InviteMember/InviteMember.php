<?php

declare(strict_types=1);

namespace Source\Account\Invitation\Application\UseCase\Command\InviteMember;

use Source\Account\Account\Domain\Entity\Account;
use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\Invitation\Application\Exception\DisallowedInvitationException;
use Source\Account\Invitation\Domain\Event\InvitationCreated;
use Source\Account\Invitation\Domain\Factory\InvitationFactoryInterface;
use Source\Account\Invitation\Domain\Repository\InvitationRepositoryInterface;
use Source\Account\Invitation\Domain\Service\InvitationMailServiceInterface;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Account\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Account\Principal\Domain\ValueObject\Action;
use Source\Account\Principal\Domain\ValueObject\Resource;
use Source\Account\Shared\Domain\ValueObject\AccountType;
use Source\Identity\Domain\Repository\IdentityRepositoryInterface;
use Source\Shared\Application\Service\Event\EventDispatcherInterface;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\Language;

readonly class InviteMember implements InviteMemberInterface
{
    public function __construct(
        private InvitationRepositoryInterface $invitationRepository,
        private InvitationFactoryInterface $invitationFactory,
        private PolicyEvaluatorInterface $policyEvaluator,
        private PrincipalRepositoryInterface $principalRepository,
        private AccountRepositoryInterface $accountRepository,
        private IdentityRepositoryInterface $identityRepository,
        private InvitationMailServiceInterface $invitationMailService,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function process(InviteMemberInputPort $input, InviteMemberOutputPort $output): void
    {
        $account = $this->assertAccountAllowsInvitation($input);
        $principal = $this->findInviterPrincipal($input);
        $this->assertInviterHasPermission($input, $principal, $account);

        $invitations = [];
        foreach ($input->emails() as $email) {
            $existingEmailNotificationLanguage = $this->existingEmailNotificationLanguage($email, $principal);
            if ($existingEmailNotificationLanguage !== null) {
                $this->invitationMailService->sendExistingEmailNotification(
                    $email,
                    $existingEmailNotificationLanguage
                );

                continue;
            }

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

    private function existingEmailNotificationLanguage(Email $email, Principal $inviter): ?Language
    {
        $identity = $this->identityRepository->findByEmail($email);
        if ($identity !== null) {
            return $identity->language();
        }

        if ($this->accountRepository->findByEmail($email) === null) {
            return null;
        }

        return $this->identityRepository->findById($inviter->identityIdentifier())?->language()
            ?? Language::ENGLISH;
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
        Account $account,
    ): void {
        $allowed = $this->policyEvaluator->evaluate(
            $principal,
            Action::INVITE_MEMBER,
            Resource::account($input->accountIdentifier(), $account->type()),
        );

        if ($allowed) {
            return;
        }

        throw new DisallowedInvitationException('招待を作成する権限がありません。');
    }

    private function assertAccountAllowsInvitation(InviteMemberInputPort $input): Account
    {
        $account = $this->accountRepository->findById($input->accountIdentifier());

        if ($account?->type() === AccountType::CORPORATION) {
            return $account;
        }

        throw new DisallowedInvitationException('法人アカウントのみメンバーを招待できます。');
    }
}
