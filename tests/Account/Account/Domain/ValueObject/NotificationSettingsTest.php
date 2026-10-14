<?php

declare(strict_types=1);

namespace Tests\Account\Account\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Account\Account\Domain\ValueObject\NotificationSettings;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\StrTestHelper;

class NotificationSettingsTest extends TestCase
{
    public function testAcceptsEmptyAndPreservesIdentifiers(): void
    {
        $identity = new IdentityIdentifier(StrTestHelper::generateUuid());
        $this->assertSame([], (new NotificationSettings([]))->identityIdentifiers());
        $settings = new NotificationSettings([$identity]);
        $this->assertSame([$identity], $settings->identityIdentifiers());
    }

    public function testRejectsDuplicates(): void
    {
        $identity = new IdentityIdentifier(StrTestHelper::generateUuid());
        $this->expectException(InvalidArgumentException::class);
        new NotificationSettings([$identity, $identity]);
    }

    public function testRejectsSixRecipients(): void
    {
        $ids = array_map(static fn (): IdentityIdentifier => new IdentityIdentifier(StrTestHelper::generateUuid()), range(1, 6));
        $this->expectException(InvalidArgumentException::class);
        new NotificationSettings($ids);
    }
}
