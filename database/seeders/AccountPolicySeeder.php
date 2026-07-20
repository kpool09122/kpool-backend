<?php

declare(strict_types=1);

namespace Database\Seeders;

use DateTimeImmutable;
use Illuminate\Database\Seeder;
use Source\Account\IdentityGroup\Domain\ValueObject\AccountRole;
use Source\Account\Policy\Domain\Entity\AccountPolicy;
use Source\Account\Policy\Domain\Repository\AccountPolicyRepositoryInterface;
use Source\Account\Policy\Domain\ValueObject\AccountAction;
use Source\Account\Policy\Domain\ValueObject\AccountPolicyIdentifier;
use Source\Account\Policy\Domain\ValueObject\AccountResourceType;
use Source\Account\Policy\Domain\ValueObject\Effect;
use Source\Account\Policy\Domain\ValueObject\Statement;


class AccountPolicySeeder extends Seeder
{
    public function __construct(
        private readonly AccountPolicyRepositoryInterface $accountPolicyRepository,
    ) {
    }

    public function run(): void
    {
        $ownerPolicy = $this->createPolicy('01982020-0456-7000-8000-000000000001', 'ACCOUNT_OWNER_BASIC', [
            new Statement(
                effect: Effect::ALLOW,
                actions: [
                    AccountAction::INVITATION_CREATE,
                    AccountAction::UPDATE_NAME,
                    AccountAction::SETTINGS_UPDATE,
                    AccountAction::DELETE,
                    AccountAction::BILLING_MANAGE,
                    AccountAction::DELEGATION_MANAGE,
                ],
                resourceTypes: [AccountResourceType::ACCOUNT],
            ),
        ]);

        $adminPolicy = $this->createPolicy('01982020-0456-7000-8000-000000000002', 'ACCOUNT_ADMIN_BASIC', [
            new Statement(
                effect: Effect::ALLOW,
                actions: [
                    AccountAction::INVITATION_CREATE,
                    AccountAction::UPDATE_NAME,
                    AccountAction::SETTINGS_UPDATE,
                    AccountAction::DELEGATION_MANAGE,
                ],
                resourceTypes: [AccountResourceType::ACCOUNT],
            ),
        ]);

        $memberPolicy = $this->createPolicy('01982020-0456-7000-8000-000000000003', 'ACCOUNT_MEMBER_BASIC', []);

        $this->accountPolicyRepository->attachToRole(AccountRole::OWNER, $ownerPolicy->accountPolicyIdentifier());
        $this->accountPolicyRepository->attachToRole(AccountRole::ADMIN, $adminPolicy->accountPolicyIdentifier());
        $this->accountPolicyRepository->attachToRole(AccountRole::MEMBER, $memberPolicy->accountPolicyIdentifier());
    }

    /**
     * @param Statement[] $statements
     */
    private function createPolicy(string $identifier, string $name, array $statements): AccountPolicy
    {
        $policy = new AccountPolicy(
            new AccountPolicyIdentifier($identifier),
            $name,
            $statements,
            true,
            new DateTimeImmutable(),
        );

        $this->accountPolicyRepository->save($policy);

        return $policy;
    }
}
