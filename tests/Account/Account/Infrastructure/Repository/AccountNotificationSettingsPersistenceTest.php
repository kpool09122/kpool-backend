<?php

declare(strict_types=1);

namespace Tests\Account\Account\Infrastructure\Repository;

use PHPUnit\Framework\Attributes\Group;
use Source\Account\Account\Domain\ValueObject\AccountName;
use Source\Account\Account\Domain\ValueObject\NotificationSettings;
use Source\Account\Account\Infrastructure\Repository\AccountRepository;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\CreateAccount;
use Tests\Helper\CreateIdentity;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class AccountNotificationSettingsPersistenceTest extends TestCase
{
    public function testReplaceAndEmptyAreScopedToOneAccount(): void
    {
        $first = new AccountIdentifier(StrTestHelper::generateUuid());
        $second = new AccountIdentifier(StrTestHelper::generateUuid());
        $identity = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateAccount::create((string) $first);
        CreateAccount::create((string) $second);
        CreateIdentity::create($identity);
        $repository = new AccountRepository();
        $firstAccount = $repository->findById($first);
        $secondAccount = $repository->findById($second);
        self::assertNotNull($firstAccount);
        self::assertNotNull($secondAccount);
        $this->assertSame([], $firstAccount->notificationSettings()->identityIdentifiers());
        foreach ([$firstAccount, $secondAccount] as $account) {
            $account->changeNotificationSettings(new NotificationSettings([$identity]));
            $repository->save($account);
        }
        $restored = $repository->findById($first);
        self::assertNotNull($restored);
        $this->assertEquals([$identity], $restored->notificationSettings()->identityIdentifiers());
        $restored->changeName(new AccountName('Updated account'));
        $repository->save($restored);
        $this->assertEquals([$identity], $repository->findByEmail($restored->email())?->notificationSettings()->identityIdentifiers());
        foreach ($repository->findByIds([$first, $second]) as $account) {
            $this->assertEquals([$identity], $account->notificationSettings()->identityIdentifiers());
        }
        $restored->changeNotificationSettings(new NotificationSettings([]));
        $repository->save($restored);
        $this->assertSame([], $repository->findById($first)?->notificationSettings()->identityIdentifiers());
        $this->assertEquals([$identity], $repository->findById($second)?->notificationSettings()->identityIdentifiers());
    }
}
