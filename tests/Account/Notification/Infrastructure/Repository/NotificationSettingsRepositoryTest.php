<?php

declare(strict_types=1);

namespace Tests\Account\Notification\Infrastructure\Repository;

use PHPUnit\Framework\Attributes\Group;
use Source\Account\Notification\Domain\Entity\NotificationSettings;
use Source\Account\Notification\Infrastructure\Repository\NotificationSettingsRepository;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\CreateAccount;
use Tests\Helper\CreateIdentity;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class NotificationSettingsRepositoryTest extends TestCase
{
    public function testReplaceAndEmptyAreScopedToOneAccount(): void
    {
        $first = new AccountIdentifier(StrTestHelper::generateUuid());
        $second = new AccountIdentifier(StrTestHelper::generateUuid());
        $identity = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateAccount::create((string) $first);
        CreateAccount::create((string) $second);
        CreateIdentity::create($identity);
        $repository = new NotificationSettingsRepository();
        $this->assertSame([], $repository->findByAccountId($first)->identityIdentifiers());
        foreach ([$first, $second] as $account) {
            $repository->save(new NotificationSettings($account, [$identity]));
        }
        $this->assertSame((string) $identity, (string) $repository->findByAccountId($first)->identityIdentifiers()[0]);
        $repository->save(new NotificationSettings($first, []));
        $this->assertSame([], $repository->findByAccountId($first)->identityIdentifiers());
        $this->assertCount(1, $repository->findByAccountId($second)->identityIdentifiers());
    }
}
