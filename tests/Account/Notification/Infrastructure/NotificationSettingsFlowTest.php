<?php

declare(strict_types=1);

namespace Tests\Account\Notification\Infrastructure;

use Database\Seeders\AccountAuthorizationSeeder;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Mockery;
use PHPUnit\Framework\Attributes\Group;
use Source\Account\Account\Application\Exception\AccountUpdateForbiddenException;
use Source\Account\Account\Application\UseCase\Command\CreateAccount\CreateAccountInput;
use Source\Account\Account\Application\UseCase\Command\CreateAccount\CreateAccountInterface;
use Source\Account\Account\Application\UseCase\Command\CreateAccount\CreateAccountOutput;
use Source\Account\Account\Domain\ValueObject\AccountName;
use Source\Account\Notification\Application\UseCase\Command\UpdateNotificationSettings\UpdateNotificationSettingsInput;
use Source\Account\Notification\Application\UseCase\Command\UpdateNotificationSettings\UpdateNotificationSettingsInterface;
use Source\Account\Notification\Application\UseCase\Command\UpdateNotificationSettings\UpdateNotificationSettingsOutput;
use Source\Account\Notification\Application\UseCase\Query\GetNotificationSettings\GetNotificationSettingsInput;
use Source\Account\Notification\Application\UseCase\Query\GetNotificationSettings\GetNotificationSettingsInterface;
use Source\Account\Principal\Domain\Entity\Policy;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Principal\Domain\Entity\PrincipalGroup;
use Source\Account\Principal\Domain\Entity\Role;
use Source\Account\Principal\Domain\Repository\PolicyRepositoryInterface;
use Source\Account\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Account\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Account\Principal\Domain\Repository\RoleRepositoryInterface;
use Source\Account\Principal\Domain\ValueObject\Action;
use Source\Account\Principal\Domain\ValueObject\Effect;
use Source\Account\Principal\Domain\ValueObject\PolicyIdentifier;
use Source\Account\Principal\Domain\ValueObject\ResourceType;
use Source\Account\Principal\Domain\ValueObject\RoleIdentifier;
use Source\Account\Principal\Domain\ValueObject\Statement;
use Source\Account\Shared\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Application\Service\AccountNotificationRecipientServiceInterface;
use Source\Shared\Application\Service\Event\EventDispatcherInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Tests\Helper\CreateAccount;
use Tests\Helper\CreateIdentity;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class NotificationSettingsFlowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AccountAuthorizationSeeder::class);
    }

    public function testNewAccountDefaultsToCreatorAndCanDisableNotifications(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $email = new Email('creator@example.com');
        CreateIdentity::create($identityIdentifier, ['email' => (string) $email, 'language' => 'ja']);
        $this->app()->instance(EventDispatcherInterface::class, Mockery::spy(EventDispatcherInterface::class));
        $output = new CreateAccountOutput();
        $this->app()->make(CreateAccountInterface::class)->process(
            new CreateAccountInput($email, new AccountName('My Account'), $identityIdentifier),
            $output,
        );
        $result = $output->toArray();
        self::assertNotEmpty($result);
        $account = new AccountIdentifier($result['accountIdentifier'] ?? '');
        $owner = $this->app()->make(PrincipalRepositoryInterface::class)->findByIdentityIdentifierAndAccountIdentifier($identityIdentifier, $account);
        self::assertNotNull($owner);

        $this->assertSame([['identityIdentifier' => (string) $identityIdentifier, 'channel' => 'email']], $this->getSettings($account, $owner)['recipients']);
        $service = $this->app()->make(AccountNotificationRecipientServiceInterface::class);
        $recipients = $service->resolve([$account]);
        $this->assertCount(1, $recipients);
        $this->assertSame((string) $email, (string) $recipients[0]->email());
        $this->assertSame(Language::JAPANESE, $recipients[0]->language());

        $this->update($account, $owner, []);
        $this->assertSame([], $this->getSettings($account, $owner)['recipients']);
        $this->assertSame([], $service->resolve([$account]));
    }

    public function testOwnerCanSaveNonOwnerAndResolveCurrentEmailAndLanguage(): void
    {
        [$account, $owner, $members] = $this->account(3);
        $this->assertSame([], $this->getSettings($account, $owner)['recipients']);
        $this->assertCount(3, $this->getSettings($account, $owner)['candidates']);
        $this->assertSame(3, $this->getSettings($account, $owner)['maxRecipients']);
        $this->update($account, $owner, [$members[1]]);
        $this->assertSame([['identityIdentifier' => (string) $members[1], 'channel' => 'email']], $this->getSettings($account, $owner)['recipients']);
        DB::table('identities')->where('id', (string) $members[1])->update(['email' => 'changed@example.com', 'language' => 'en']);
        $recipients = $this->app()->make(AccountNotificationRecipientServiceInterface::class)->resolve([$account]);
        $this->assertCount(1, $recipients);
        $this->assertSame('changed@example.com', (string) $recipients[0]->email());
        $this->assertSame(Language::ENGLISH, $recipients[0]->language());
        DB::table('account_principals')->where('identity_id', (string) $members[1])->delete();
        $this->assertSame([], $this->app()->make(AccountNotificationRecipientServiceInterface::class)->resolve([$account]));
        $this->assertSame([], $this->getSettings($account, $owner)['recipients']);
    }

    public function testZeroThreeAndFiveRecipientsCanBeSavedAndSixRejected(): void
    {
        [$account, $owner, $members] = $this->account(6);
        foreach ([0, 1, 3, 5] as $count) {
            $this->update($account, $owner, array_slice($members, 0, $count));
            $this->assertCount($count, $this->getSettings($account, $owner)['recipients']);
        }
        $this->expectException(InvalidArgumentException::class);
        $this->update($account, $owner, $members);
    }

    public function testThreeMembersCanAllBeSaved(): void
    {
        [$account, $owner, $members] = $this->account(3);
        $this->update($account, $owner, $members);
        $this->assertCount(3, $this->getSettings($account, $owner)['recipients']);
    }

    public function testUnconfiguredAndEmptySettingsDoNotResolveRecipients(): void
    {
        [$account, $owner] = $this->account(1);
        $service = $this->app()->make(AccountNotificationRecipientServiceInterface::class);
        $this->assertSame([], $service->resolve([$account]));
        $this->update($account, $owner, []);
        $this->assertSame([], $service->resolve([$account]));
    }

    public function testWithdrawnIdentityIsNotResolved(): void
    {
        [$account, $owner, $members] = $this->account(2);
        $this->update($account, $owner, [$members[1]]);
        DB::table('identities')->where('id', (string) $members[1])->delete();
        $this->assertSame([], $this->app()->make(AccountNotificationRecipientServiceInterface::class)->resolve([$account]));
    }

    public function testDuplicateAndExternalRecipientsAreRejectedWithoutChangingSettings(): void
    {
        [$account, $owner, $members] = $this->account(2);
        $this->update($account, $owner, [$members[0]]);
        foreach ([[$members[1], $members[1]], [new IdentityIdentifier(StrTestHelper::generateUuid())]] as $ids) {
            try {
                $this->update($account, $owner, $ids);
                $this->fail('Invalid recipients accepted.');
            } catch (InvalidArgumentException) {
                $this->assertSame((string) $members[0], $this->getSettings($account, $owner)['recipients'][0]['identityIdentifier']);
            }
        }
    }

    public function testPoliciesAllowCustomRolesAndDistinguishReadFromUpdate(): void
    {
        [$account, $owner, $members] = $this->account(2);
        $member = $this->app()->make(PrincipalRepositoryInterface::class)->findByIdentityIdentifierAndAccountIdentifier($members[1], $account);
        self::assertNotNull($member);
        $this->grant($member, [Action::NOTIFICATION_SETTINGS_READ]);
        $this->assertCount(2, $this->getSettings($account, $member)['candidates']);

        try {
            $this->update($account, $member, [$members[1]]);
            $this->fail('Read permission must not allow updates.');
        } catch (AccountUpdateForbiddenException) {
            $this->assertSame([], $this->getSettings($account, $owner)['recipients']);
        }
        $this->grant($member, [Action::NOTIFICATION_SETTINGS_UPDATE]);
        $this->update($account, $member, [$members[1]]);
        $this->assertSame((string) $members[1], $this->getSettings($account, $member)['recipients'][0]['identityIdentifier']);
    }

    public function testGenericAccountPermissionsDoNotGrantNotificationSettingsPermissions(): void
    {
        [$account, $owner, $members] = $this->account(2);
        $member = $this->app()->make(PrincipalRepositoryInterface::class)->findByIdentityIdentifierAndAccountIdentifier($members[1], $account);
        self::assertNotNull($member);
        $this->grant($member, [Action::READ, Action::UPDATE]);
        $denied = 0;
        foreach (['read', 'update'] as $operation) {
            try {
                $operation === 'read' ? $this->getSettings($account, $member) : $this->update($account, $member, []);
                $this->fail('Notification settings require their own permissions.');
            } catch (AccountUpdateForbiddenException) {
                $denied++;
            }
        }
        $this->assertSame(2, $denied);
    }

    public function testGroupNameDoesNotDeterminePermissionAndExplicitDenyWins(): void
    {
        [$account, $owner, $members] = $this->account(2);
        DB::table('account_principal_groups')->where('account_id', (string) $account)->update(['name' => 'Editors']);
        $this->update($account, $owner, [$members[0]]);
        $this->grant($owner, [Action::NOTIFICATION_SETTINGS_UPDATE], Effect::DENY);
        $this->expectException(AccountUpdateForbiddenException::class);
        $this->update($account, $owner, []);
    }

    public function testOtherAccountCannotBeReadOrUpdated(): void
    {
        [$account, $owner, $members] = $this->account(1);
        [$otherAccount] = $this->account(1);
        $denied = 0;
        foreach (['read', 'update'] as $operation) {
            try {
                $operation === 'read' ? $this->getSettings($otherAccount, $owner) : $this->update($otherAccount, $owner, $members);
                $this->fail('Other account access must be rejected.');
            } catch (AccountUpdateForbiddenException) {
                $denied++;
            }
        }
        $this->assertSame(2, $denied);
        $this->assertSame([], $this->getSettings($account, $owner)['recipients']);
    }

    public function testMembersWithoutPoliciesCannotReadOrUpdate(): void
    {
        [$account, $owner, $members] = $this->account(2);
        $nonOwner = $this->app()->make(PrincipalRepositoryInterface::class)->findByIdentityIdentifierAndAccountIdentifier($members[1], $account);
        assert($nonOwner !== null);
        $denied = 0;
        foreach (['read', 'update'] as $operation) {
            try {
                $operation === 'read' ? $this->getSettings($account, $nonOwner) : $this->update($account, $nonOwner, []);

                throw new InvalidArgumentException('Non-owner access accepted.');
            } catch (AccountUpdateForbiddenException) {
                $denied++;
            }
        }
        $this->assertSame(2, $denied);
        [$otherAccount] = $this->account(1);
        $this->expectException(AccountUpdateForbiddenException::class);
        $this->getSettings($otherAccount, $owner);
    }

    /** @param Action[] $actions */
    private function grant(Principal $principal, array $actions, Effect $effect = Effect::ALLOW): void
    {
        $policy = new Policy(new PolicyIdentifier(StrTestHelper::generateUuid()), 'Notification policy ' . StrTestHelper::generateUuid(), [new Statement($effect, $actions, [ResourceType::ACCOUNT])], $principal->accountIdentifier(), new DateTimeImmutable());
        $this->app()->make(PolicyRepositoryInterface::class)->save($policy);
        $role = new Role(new RoleIdentifier(StrTestHelper::generateUuid()), 'Custom role ' . StrTestHelper::generateUuid(), [$policy->policyIdentifier()], $principal->accountIdentifier());
        $this->app()->make(RoleRepositoryInterface::class)->save($role);
        $group = new PrincipalGroup(new PrincipalGroupIdentifier(StrTestHelper::generateUuid()), $principal->accountIdentifier(), 'Custom group ' . StrTestHelper::generateUuid(), false, new DateTimeImmutable());
        $group->addRole($role);
        $group->addMember($principal->principalIdentifier());
        $this->app()->make(PrincipalGroupRepositoryInterface::class)->save($group);
    }

    /** @return array{AccountIdentifier, Principal, list<IdentityIdentifier>} */
    private function account(int $count): array
    {
        $account = new AccountIdentifier(StrTestHelper::generateUuid());
        CreateAccount::create((string) $account);
        $group = StrTestHelper::generateUuid();
        DB::table('account_principal_groups')->insert(['id' => $group, 'account_id' => (string) $account, 'name' => 'Owners', 'is_default' => false, 'created_at' => now(), 'updated_at' => now()]);
        $ownerRole = $this->app()->make(RoleRepositoryInterface::class)->findSystemByName(Role::OWNER);
        self::assertNotNull($ownerRole);
        DB::table('account_principal_group_role_attachments')->insert(['principal_group_id' => $group, 'role_id' => (string) $ownerRole->roleIdentifier()]);
        $members = [];
        $owner = null;
        for ($i = 0; $i < $count; $i++) {
            $id = new IdentityIdentifier(StrTestHelper::generateUuid());
            CreateIdentity::create($id, ['email' => (string) $id . '@example.com']);
            $principal = new Principal(new PrincipalIdentifier(StrTestHelper::generateUuid()), $id, $account);
            DB::table('account_principals')->insert(['id' => (string) $principal->principalIdentifier(), 'identity_id' => (string) $id, 'account_id' => (string) $account, 'created_at' => now(), 'updated_at' => now()]);
            if ($i === 0) {
                $owner = $principal;
                DB::table('account_principal_group_memberships')->insert(['id' => StrTestHelper::generateUuid(), 'principal_group_id' => $group, 'principal_id' => (string) $principal->principalIdentifier(), 'created_at' => now(), 'updated_at' => now()]);
            }
            $members[] = $id;
        }
        assert($owner instanceof Principal);

        return [$account, $owner, $members];
    }

    /** @param IdentityIdentifier[] $members
     * @throws AccountUpdateForbiddenException
     * @throws InvalidArgumentException
     */
    private function update(AccountIdentifier $account, Principal $principal, array $members): void
    {
        $output = new UpdateNotificationSettingsOutput();
        $this->app()->make(UpdateNotificationSettingsInterface::class)->process(new UpdateNotificationSettingsInput($account, $principal, $members), $output);
        $this->assertSame([], $output->toArray());
    }

    /**
     * @return array{recipients: list<array{identityIdentifier: string, channel: string}>, candidates: list<array{identityIdentifier: string, name: string, email: string, language: string}>, maxRecipients: int}
     * @throws AccountUpdateForbiddenException
     */
    private function getSettings(AccountIdentifier $account, Principal $principal): array
    {
        return $this->app()->make(GetNotificationSettingsInterface::class)->process(new GetNotificationSettingsInput($account, $principal))->toArray();
    }
}
