<?php

declare(strict_types=1);

namespace Tests\Helper;

use DateTimeImmutable;
use Source\Account\Account\Domain\Entity\Account;
use Source\Account\Account\Domain\ValueObject\AccountDocuments;
use Source\Account\Account\Domain\ValueObject\AccountName;
use Source\Account\Account\Domain\ValueObject\AccountStatus;
use Source\Account\Account\Domain\ValueObject\DeletionReadinessChecklist;
use Source\Account\Principal\Domain\Entity\Principal as AccountPrincipal;
use Source\Account\Principal\Domain\Entity\PrincipalGroup as AccountPrincipalGroup;
use Source\Account\Principal\Domain\Entity\Role as AccountRole;
use Source\Account\Principal\Domain\ValueObject\RoleIdentifier as AccountRoleIdentifier;
use Source\Account\Shared\Domain\ValueObject\AccountType;
use Source\Account\Shared\Domain\ValueObject\PrincipalGroupIdentifier as AccountPrincipalGroupIdentifier;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier as AccountPrincipalIdentifier;
use Source\Identity\Domain\Entity\Identity;
use Source\Identity\Domain\ValueObject\IdentityName;
use Source\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Principal\Domain\Entity\Principal as WikiPrincipal;
use Source\Wiki\Principal\Domain\Entity\PrincipalGroup as WikiPrincipalGroup;
use Source\Wiki\Principal\Domain\Entity\Role as WikiRole;
use Source\Wiki\Principal\Domain\ValueObject\PrincipalGroupIdentifier as WikiPrincipalGroupIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\RoleIdentifier as WikiRoleIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier as WikiPrincipalIdentifier;

readonly class WikiAdministratorTestData
{
    public function __construct(
        public Email $email,
        public Identity $identity,
        public Account $account,
        public AccountPrincipal $accountPrincipal,
        public AccountRole $operationsRole,
        public AccountPrincipalGroup $operationsGroup,
        public WikiPrincipal $wikiPrincipal,
        public WikiRole $administratorRole,
        public WikiPrincipalGroup $administratorGroup,
    ) {
    }

    public static function create(): self
    {
        $email = new Email('operator@example.com');
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $accountPrincipal = new AccountPrincipal(
            new AccountPrincipalIdentifier(StrTestHelper::generateUuid()),
            $identityIdentifier,
            $accountIdentifier,
        );
        $operationsRole = new AccountRole(
            new AccountRoleIdentifier(StrTestHelper::generateUuid()),
            AccountRole::OPERATIONS,
            [],
            null,
        );
        $operationsGroup = new AccountPrincipalGroup(
            new AccountPrincipalGroupIdentifier(StrTestHelper::generateUuid()),
            $accountIdentifier,
            'Operations',
            false,
            new DateTimeImmutable(),
            [$operationsRole->roleIdentifier()],
        );
        $operationsGroup->addMember($accountPrincipal->principalIdentifier());

        $wikiPrincipal = new WikiPrincipal(
            new WikiPrincipalIdentifier(StrTestHelper::generateUuid()),
            $identityIdentifier,
            $accountIdentifier,
        );
        $administratorRole = new WikiRole(
            new WikiRoleIdentifier(StrTestHelper::generateUuid()),
            'ADMINISTRATOR',
            [],
            null,
            new DateTimeImmutable(),
        );
        $administratorGroup = new WikiPrincipalGroup(
            new WikiPrincipalGroupIdentifier(StrTestHelper::generateUuid()),
            $accountIdentifier,
            'Operations Wiki Administrators',
            false,
            new DateTimeImmutable(),
        );

        return new self(
            $email,
            new Identity(
                $identityIdentifier,
                new IdentityName('Operator'),
                $email,
                Language::JAPANESE,
                null,
                new DateTimeImmutable(),
            ),
            new Account(
                $accountIdentifier,
                $email,
                AccountType::INDIVIDUAL,
                new AccountName('Existing Account'),
                AccountStatus::ACTIVE,
                AccountCategory::GENERAL,
                DeletionReadinessChecklist::ready(),
                new AccountDocuments(),
            ),
            $accountPrincipal,
            $operationsRole,
            $operationsGroup,
            $wikiPrincipal,
            $administratorRole,
            $administratorGroup,
        );
    }
}
