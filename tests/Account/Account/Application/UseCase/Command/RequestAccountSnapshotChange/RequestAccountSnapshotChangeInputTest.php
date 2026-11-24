<?php

declare(strict_types=1);

namespace Tests\Account\Account\Application\UseCase\Command\RequestAccountSnapshotChange;

use PHPUnit\Framework\TestCase;
use Source\Account\Account\Application\UseCase\Command\RequestAccountSnapshotChange\RequestAccountSnapshotChangeInput;
use Source\Account\Account\Domain\ValueObject\AccountSnapshotChangeReason;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\StrTestHelper;

class RequestAccountSnapshotChangeInputTest extends TestCase
{
    public function test__construct(): void
    {
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal(
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            $accountIdentifier,
        );
        $changeReason = new AccountSnapshotChangeReason('住所変更');

        $input = new RequestAccountSnapshotChangeInput(
            $accountIdentifier,
            $principal,
            $changeReason,
        );

        $this->assertSame($accountIdentifier, $input->accountIdentifier());
        $this->assertSame($principal, $input->principal());
        $this->assertSame($changeReason, $input->changeReason());
    }
}
