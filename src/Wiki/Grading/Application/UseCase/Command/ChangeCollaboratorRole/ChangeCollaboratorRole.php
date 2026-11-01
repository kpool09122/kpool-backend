<?php

declare(strict_types=1);

namespace Source\Wiki\Grading\Application\UseCase\Command\ChangeCollaboratorRole;

use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Identity\Domain\Exception\IdentityNotFoundException;
use Source\Identity\Domain\Repository\IdentityRepositoryInterface;
use Source\Wiki\Grading\Application\Service\CollaboratorRoleTransitionServiceInterface;
use Source\Wiki\Grading\Domain\Facotory\PromotionHistoryFactoryInterface;
use Source\Wiki\Grading\Domain\Repository\DemotionWarningRepositoryInterface;
use Source\Wiki\Grading\Domain\Repository\PromotionHistoryRepositoryInterface;
use Source\Wiki\Grading\Domain\ValueObject\CollaboratorRole;
use Source\Wiki\Principal\Application\Exception\PrincipalNotFoundException;
use Source\Wiki\Principal\Application\Exception\SystemRoleNotFoundException;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Repository\RoleRepositoryInterface;

readonly class ChangeCollaboratorRole implements ChangeCollaboratorRoleInterface
{
    public function __construct(
        private IdentityRepositoryInterface $identityRepository,
        private AccountRepositoryInterface $accountRepository,
        private PrincipalRepositoryInterface $principalRepository,
        private RoleRepositoryInterface $roleRepository,
        private CollaboratorRoleTransitionServiceInterface $collaboratorRoleTransitionService,
        private PromotionHistoryRepositoryInterface $promotionHistoryRepository,
        private PromotionHistoryFactoryInterface $promotionHistoryFactory,
        private DemotionWarningRepositoryInterface $demotionWarningRepository,
    ) {
    }

    public function process(ChangeCollaboratorRoleInputPort $input, ChangeCollaboratorRoleOutputPort $output): void
    {
        $identity = $this->identityRepository->findByEmail($input->email())
            ?? throw new IdentityNotFoundException('Identityが見つかりません。');
        $account = $this->accountRepository->findByEmail($input->email())
            ?? throw new AccountNotFoundException('指定されたメールアドレスのAccountが見つかりません。');
        $principal = $this->principalRepository->findByIdentityIdentifierAndAccountIdentifier($identity->identityIdentifier(), $account->accountIdentifier())
            ?? throw new PrincipalNotFoundException('指定されたメールアドレスのWiki Principalが見つかりません。');
        $seniorRole = $this->roleRepository->findSystemByName(CollaboratorRole::SENIOR_COLLABORATOR->value)
            ?? throw new SystemRoleNotFoundException(CollaboratorRole::SENIOR_COLLABORATOR->value);
        $promoting = $input->targetRole() === CollaboratorRole::SENIOR_COLLABORATOR;
        $changed = $promoting
            ? $this->collaboratorRoleTransitionService->promote($principal->principalIdentifier(), $seniorRole)
            : $this->collaboratorRoleTransitionService->demote($principal->principalIdentifier(), $seniorRole);
        $output->setChanged($changed);
        if (! $changed) {
            return;
        }

        $warning = $this->demotionWarningRepository->findByPrincipal($principal->principalIdentifier());
        if ($warning !== null) {
            $this->demotionWarningRepository->delete($warning);
        }
        $this->promotionHistoryRepository->save($this->promotionHistoryFactory->create(
            $principal->principalIdentifier(),
            $promoting ? CollaboratorRole::COLLABORATOR->value : CollaboratorRole::SENIOR_COLLABORATOR->value,
            $input->targetRole()->value,
            $promoting ? 'Manually promoted by console command' : 'Manually demoted by console command',
        ));
    }
}
