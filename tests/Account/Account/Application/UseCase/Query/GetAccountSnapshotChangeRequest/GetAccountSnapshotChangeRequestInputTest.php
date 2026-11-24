<?php

declare(strict_types=1);

namespace Tests\Account\Account\Application\UseCase\Query\GetAccountSnapshotChangeRequest;

use PHPUnit\Framework\TestCase;
use Source\Account\Account\Application\UseCase\Query\GetAccountSnapshotChangeRequest\GetAccountSnapshotChangeRequestInput;
use Source\Account\Account\Domain\ValueObject\AccountSnapshotChangeRequestIdentifier;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\StrTestHelper;

class GetAccountSnapshotChangeRequestInputTest extends TestCase
{
    public function test__construct(): void
    {
        $requestIdentifier = new AccountSnapshotChangeRequestIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal(
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            new AccountIdentifier(StrTestHelper::generateUuid()),
        );

        $input = new GetAccountSnapshotChangeRequestInput(
            $requestIdentifier,
            $principal,
        );

        $this->assertSame($requestIdentifier, $input->requestIdentifier());
        $this->assertSame($principal, $input->principal());
    }
}
