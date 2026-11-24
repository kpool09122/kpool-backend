<?php

declare(strict_types=1);

namespace Tests\Account\Account\Application\UseCase\Command\ApproveAccountSnapshotChangeRequest;

use PHPUnit\Framework\TestCase;
use Source\Account\Account\Application\UseCase\Command\ApproveAccountSnapshotChangeRequest\ApproveAccountSnapshotChangeRequestInput;
use Source\Account\Account\Domain\ValueObject\AccountSnapshotChangeRequestIdentifier;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\StrTestHelper;

class ApproveAccountSnapshotChangeRequestInputTest extends TestCase
{
    public function test__construct(): void
    {
        $requestIdentifier = new AccountSnapshotChangeRequestIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal(
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            new AccountIdentifier(StrTestHelper::generateUuid()),
        );

        $input = new ApproveAccountSnapshotChangeRequestInput(
            $requestIdentifier,
            $principal,
        );

        $this->assertSame($requestIdentifier, $input->requestIdentifier());
        $this->assertSame($principal, $input->principal());
    }
}
