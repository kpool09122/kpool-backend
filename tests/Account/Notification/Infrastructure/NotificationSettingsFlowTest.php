<?php

declare(strict_types=1);

namespace Tests\Account\Notification\Infrastructure;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Group;
use Source\Account\Account\Application\Exception\AccountUpdateForbiddenException;
use Source\Account\Notification\Application\UseCase\Command\UpdateNotificationSettings\UpdateNotificationSettingsInput;
use Source\Account\Notification\Application\UseCase\Command\UpdateNotificationSettings\UpdateNotificationSettingsInterface;
use Source\Account\Notification\Application\UseCase\Command\UpdateNotificationSettings\UpdateNotificationSettingsOutput;
use Source\Account\Notification\Application\UseCase\Query\GetNotificationSettings\GetNotificationSettingsInput;
use Source\Account\Notification\Application\UseCase\Query\GetNotificationSettings\GetNotificationSettingsInterface;
use Source\Account\Notification\Application\UseCase\Query\GetNotificationSettingsAccess\GetNotificationSettingsAccessInput;
use Source\Account\Notification\Application\UseCase\Query\GetNotificationSettingsAccess\GetNotificationSettingsAccessInterface;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Application\Service\AccountNotificationRecipientServiceInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Tests\Helper\CreateAccount;
use Tests\Helper\CreateIdentity;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class NotificationSettingsFlowTest extends TestCase
{
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

    public function testAccessContractOnlyExposesOwnersMembership(): void
    {
        [$account, $owner, $members] = $this->account(2);
        $query = $this->app()->make(GetNotificationSettingsAccessInterface::class);
        $this->assertTrue($query->process(new GetNotificationSettingsAccessInput($account, $owner))->canManage());
        $nonOwner = $this->app()->make(PrincipalRepositoryInterface::class)->findByIdentityIdentifierAndAccountIdentifier($members[1], $account);
        assert($nonOwner !== null);
        $this->assertFalse($query->process(new GetNotificationSettingsAccessInput($account, $nonOwner))->canManage());
    }

    public function testOtherAccountCannotBeUpdatedOrReadForAccess(): void
    {
        [$account, $owner, $members] = $this->account(1);
        [$otherAccount] = $this->account(1);
        $denied = 0;

        try {
            $this->update($otherAccount, $owner, $members);
        } catch (AccountUpdateForbiddenException) {
            $denied++;
        }
        $query = $this->app()->make(GetNotificationSettingsAccessInterface::class);

        try {
            $query->process(new GetNotificationSettingsAccessInput($otherAccount, $owner));
        } catch (AccountUpdateForbiddenException) {
            $denied++;
        }
        $this->assertSame(2, $denied);
        $this->assertSame([], $this->getSettings($account, $owner)['recipients']);
    }

    public function testNonOwnersCannotReadOrUpdate(): void
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

    /** @return array{AccountIdentifier, Principal, list<IdentityIdentifier>} */
    private function account(int $count): array
    {
        $account = new AccountIdentifier(StrTestHelper::generateUuid());
        CreateAccount::create((string) $account);
        $group = StrTestHelper::generateUuid();
        DB::table('account_principal_groups')->insert(['id' => $group, 'account_id' => (string) $account, 'name' => 'Owners', 'is_default' => false, 'created_at' => now(), 'updated_at' => now()]);
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
     * @return array{recipients: list<array{identityIdentifier: string, channel: string}>, candidates: list<array{identityIdentifier: string, name: string, email: string, language: string}>, maxRecipients: int, canManage: bool}
     * @throws AccountUpdateForbiddenException
     */
    private function getSettings(AccountIdentifier $account, Principal $principal): array
    {
        return $this->app()->make(GetNotificationSettingsInterface::class)->process(new GetNotificationSettingsInput($account, $principal))->toArray();
    }
}
