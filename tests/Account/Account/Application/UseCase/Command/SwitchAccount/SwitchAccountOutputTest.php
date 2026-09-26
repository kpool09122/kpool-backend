<?php

declare(strict_types=1);

namespace Tests\Account\Account\Application\UseCase\Command\SwitchAccount;

use LogicException;
use Source\Account\Account\Application\Service\CurrentAccount;
use Source\Account\Account\Application\UseCase\Command\SwitchAccount\SwitchAccountOutput;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class SwitchAccountOutputTest extends TestCase
{
    public function testCurrentAccountAndToArray(): void
    {
        $currentAccount = new CurrentAccount(
            originalIdentityIdentifier: new IdentityIdentifier(StrTestHelper::generateUuid()),
            originalAccountIdentifier: new AccountIdentifier(StrTestHelper::generateUuid()),
            originalPrincipalIdentifier: new PrincipalIdentifier(StrTestHelper::generateUuid()),
            effectiveAccountIdentifier: new AccountIdentifier(StrTestHelper::generateUuid()),
            effectivePrincipalIdentifier: new PrincipalIdentifier(StrTestHelper::generateUuid()),
            delegationIdentifier: new DelegationIdentifier(StrTestHelper::generateUuid()),
        );

        $output = new SwitchAccountOutput();
        $output->setCurrentAccount($currentAccount);

        $this->assertSame($currentAccount, $output->currentAccount());
        $this->assertSame([
            'originalIdentityIdentifier' => (string) $currentAccount->originalIdentityIdentifier,
            'accountIdentifier' => (string) $currentAccount->effectiveAccountIdentifier,
            'accountPrincipalIdentifier' => (string) $currentAccount->effectivePrincipalIdentifier,
            'delegationIdentifier' => (string) $currentAccount->delegationIdentifier,
        ], $output->toArray());
    }

    public function testToArrayWithOriginalAccount(): void
    {
        $currentAccount = new CurrentAccount(
            originalIdentityIdentifier: new IdentityIdentifier(StrTestHelper::generateUuid()),
            originalAccountIdentifier: new AccountIdentifier(StrTestHelper::generateUuid()),
            originalPrincipalIdentifier: new PrincipalIdentifier(StrTestHelper::generateUuid()),
            effectiveAccountIdentifier: new AccountIdentifier(StrTestHelper::generateUuid()),
            effectivePrincipalIdentifier: new PrincipalIdentifier(StrTestHelper::generateUuid()),
            delegationIdentifier: null,
        );

        $output = new SwitchAccountOutput();
        $output->setCurrentAccount($currentAccount);

        $this->assertNull($output->toArray()['delegationIdentifier']);
    }

    public function testCurrentAccountWithoutSet(): void
    {
        $this->expectException(LogicException::class);

        (new SwitchAccountOutput())->currentAccount();
    }
}
