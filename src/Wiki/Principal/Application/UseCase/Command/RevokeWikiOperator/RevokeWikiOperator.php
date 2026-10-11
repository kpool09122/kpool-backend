<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\RevokeWikiOperator;

use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Wiki\Principal\Application\Exception\OperatorRoleNotAttachedException;
use Source\Wiki\Principal\Application\Exception\PrincipalGroupNotFoundException;
use Source\Wiki\Principal\Application\Exception\SystemRoleNotFoundException;
use Source\Wiki\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Wiki\Principal\Domain\Repository\RoleRepositoryInterface;

readonly class RevokeWikiOperator implements RevokeWikiOperatorInterface
{
    private const string OPERATOR_ROLE_NAME = 'Operator';
    private const string OPERATOR_GROUP_NAME = 'Operations Wiki Operators';

    public function __construct(
        private AccountRepositoryInterface $accountRepository,
        private PrincipalGroupRepositoryInterface $principalGroupRepository,
        private RoleRepositoryInterface $roleRepository,
    ) {
    }

    public function process(RevokeWikiOperatorInputPort $input, RevokeWikiOperatorOutputPort $output): void
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
            throw new PrincipalGroupNotFoundException('Operations Wiki Operatorsグループが見つかりません。');
        }
        if (! $principalGroup->hasRole($operatorRole->roleIdentifier())) {
            throw new OperatorRoleNotAttachedException();
        }

        $this->principalGroupRepository->delete($principalGroup);
    }
}
