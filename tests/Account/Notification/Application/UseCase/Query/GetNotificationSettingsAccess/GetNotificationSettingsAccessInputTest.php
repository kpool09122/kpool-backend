<?php

declare(strict_types=1);

namespace Tests\Account\Notification\Application\UseCase\Query\GetNotificationSettingsAccess;

use PHPUnit\Framework\TestCase;
use Source\Account\Notification\Application\UseCase\Query\GetNotificationSettingsAccess\GetNotificationSettingsAccessInput;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\StrTestHelper;

class GetNotificationSettingsAccessInputTest extends TestCase
{
    public function testPreservesExactValueObjects(): void
    {
        $account = new AccountIdentifier(StrTestHelper::generateUuid());
        $id = new IdentityIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal(new PrincipalIdentifier(StrTestHelper::generateUuid()), $id, $account);
        $input = new GetNotificationSettingsAccessInput($account, $principal);
        $this->assertSame($account, $input->accountIdentifier());
        $this->assertSame($principal, $input->principal());

    }
}
