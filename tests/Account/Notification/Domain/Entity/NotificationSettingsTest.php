<?php

declare(strict_types=1);

namespace Tests\Account\Notification\Domain\Entity;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Account\Notification\Domain\Entity\NotificationSettings;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\StrTestHelper;

class NotificationSettingsTest extends TestCase
{
    public function testAcceptsEmptyAndPreservesIdentifiers(): void
    {
        $account = new AccountIdentifier(StrTestHelper::generateUuid());
        $identity = new IdentityIdentifier(StrTestHelper::generateUuid());
        $this->assertSame([], (new NotificationSettings($account, []))->identityIdentifiers());
        $settings = new NotificationSettings($account, [$identity]);
        $this->assertSame($account, $settings->accountIdentifier());
        $this->assertSame([$identity], $settings->identityIdentifiers());
    }

    public function testRejectsDuplicates(): void
    {
        $identity = new IdentityIdentifier(StrTestHelper::generateUuid());
        $this->expectException(InvalidArgumentException::class);
        new NotificationSettings(new AccountIdentifier(StrTestHelper::generateUuid()), [$identity, $identity]);
    }

    public function testRejectsSixRecipients(): void
    {
        $ids = array_map(static fn (): IdentityIdentifier => new IdentityIdentifier(StrTestHelper::generateUuid()), range(1, 6));
        $this->expectException(InvalidArgumentException::class);
        new NotificationSettings(new AccountIdentifier(StrTestHelper::generateUuid()), $ids);
    }
}
