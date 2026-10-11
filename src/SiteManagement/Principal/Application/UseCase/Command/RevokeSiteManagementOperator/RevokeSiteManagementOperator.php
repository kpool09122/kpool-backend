<?php

declare(strict_types=1);

namespace Source\SiteManagement\Principal\Application\UseCase\Command\RevokeSiteManagementOperator;

use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\SiteManagement\Principal\Application\Exception\OperatorGroupIsDefaultException;
use Source\SiteManagement\Principal\Application\Exception\OperatorRoleNotAttachedException;
use Source\SiteManagement\Principal\Application\Exception\PrincipalGroupNotFoundException;
use Source\SiteManagement\Principal\Application\Exception\SystemRoleNotFoundException;
use Source\SiteManagement\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\SiteManagement\Principal\Domain\Repository\RoleRepositoryInterface;

readonly class RevokeSiteManagementOperator implements RevokeSiteManagementOperatorInterface
{
    private const string OPERATOR_ROLE_NAME = 'Operator';
    private const string OPERATOR_GROUP_NAME = 'Operations SiteManagement Operators';

    public function __construct(
        private AccountRepositoryInterface $accountRepository,
        private PrincipalGroupRepositoryInterface $principalGroupRepository,
        private RoleRepositoryInterface $roleRepository,
    ) {
    }

    public function process(RevokeSiteManagementOperatorInputPort $input, RevokeSiteManagementOperatorOutputPort $output): void
    {
        $account = $this->accountRepository->findByEmail($input->email());
        if ($account === null) {
            throw new AccountNotFoundException('指定されたメールアドレスのAccountが見つかりません。');
        }

        $operatorRole = $this->roleRepository->findSystemByName(self::OPERATOR_ROLE_NAME);
        if ($operatorRole === null) {
            throw new SystemRoleNotFoundException(self::OPERATOR_ROLE_NAME);
        }

        $principalGroup = $this->principalGroupRepository->findByAccountIdAndName(
            $account->accountIdentifier(),
            self::OPERATOR_GROUP_NAME,
        );
        if ($principalGroup === null) {
            throw new PrincipalGroupNotFoundException('Operations SiteManagement Operatorsグループが見つかりません。');
        }
        if ($principalGroup->isDefault()) {
            throw new OperatorGroupIsDefaultException();
        }

        if (! $principalGroup->hasRole($operatorRole->roleIdentifier())) {
            throw new OperatorRoleNotAttachedException();
        }

        $this->principalGroupRepository->delete($principalGroup);
    }
}
