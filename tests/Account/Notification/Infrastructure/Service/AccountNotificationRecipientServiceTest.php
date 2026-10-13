<?php

declare(strict_types=1);

namespace Tests\Account\Notification\Infrastructure\Service;

use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Source\Account\Notification\Domain\Entity\NotificationSettings;
use Source\Account\Notification\Infrastructure\Repository\NotificationSettingsRepository;
use Source\Account\Notification\Infrastructure\Service\AccountNotificationRecipientService;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\CreateAccount;
use Tests\Helper\CreateIdentity;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class AccountNotificationRecipientServiceTest extends TestCase
{
    public function testDeduplicatesOnlySelectedAccountsAndDoesNotFollowIdentityToOtherAccounts(): void
    {
        $identity = new IdentityIdentifier(StrTestHelper::generateUuid());
        $other = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($identity, ['email' => 'shared@example.com']);
        CreateIdentity::create($other, ['email' => 'other@example.com']);
        $accounts = [];
        for ($i = 0; $i < 3; $i++) {
            $account = new AccountIdentifier(StrTestHelper::generateUuid());
            CreateAccount::create((string) $account);
            $id = $i === 2 ? $other : $identity;
            DB::table('account_principals')->insert(['id' => StrTestHelper::generateUuid(), 'account_id' => (string) $account, 'identity_id' => (string) $id, 'created_at' => now(), 'updated_at' => now()]);
            (new NotificationSettingsRepository())->save(new NotificationSettings($account, [$id]));
            $accounts[] = $account;
        }
        $service = new AccountNotificationRecipientService();
        $this->assertSame([], $service->resolve([]));
        $recipients = $service->resolve(array_slice($accounts, 0, 2));
        $this->assertCount(1, $recipients);
        $this->assertSame('shared@example.com', (string) $recipients[0]->email());
        (new NotificationSettingsRepository())->save(new NotificationSettings($accounts[0], []));
        $this->assertSame([], $service->resolve([$accounts[0]]));
        $this->assertCount(1, $service->resolve([$accounts[1]]));
    }
}
