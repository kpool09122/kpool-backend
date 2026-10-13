<?php

declare(strict_types=1);

namespace Tests\Account\Notification\Application\UseCase\Query\GetNotificationSettings;

use PHPUnit\Framework\TestCase;
use Source\Account\Notification\Application\UseCase\Query\GetNotificationSettings\GetNotificationSettingsInput;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\StrTestHelper;

class GetNotificationSettingsInputTest extends TestCase
{
    public function testPreservesExactValueObjects(): void
    {
        $account = new AccountIdentifier(StrTestHelper::generateUuid());
        $id = new IdentityIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal(new PrincipalIdentifier(StrTestHelper::generateUuid()), $id, $account);
        $input = new GetNotificationSettingsInput($account, $principal);
        $this->assertSame($account, $input->accountIdentifier());
        $this->assertSame($principal, $input->principal());

    }
}
