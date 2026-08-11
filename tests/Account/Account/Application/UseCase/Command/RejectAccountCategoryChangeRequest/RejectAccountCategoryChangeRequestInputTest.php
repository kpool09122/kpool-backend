<?php

declare(strict_types=1);

namespace Tests\Account\Account\Application\UseCase\Command\RejectAccountCategoryChangeRequest;

use PHPUnit\Framework\TestCase;
use Source\Account\Account\Application\UseCase\Command\RejectAccountCategoryChangeRequest\RejectAccountCategoryChangeRequestInput;
use Source\Account\Account\Domain\ValueObject\AccountCategoryChangeRequestIdentifier;
use Source\Account\Account\Domain\ValueObject\RejectionReason;
use Source\Account\Account\Domain\ValueObject\RejectionReasonCode;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\StrTestHelper;

class RejectAccountCategoryChangeRequestInputTest extends TestCase
{
    public function test__construct(): void
    {
        $requestIdentifier = new AccountCategoryChangeRequestIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal(new PrincipalIdentifier(StrTestHelper::generateUuid()), new IdentityIdentifier(StrTestHelper::generateUuid()), new AccountIdentifier(StrTestHelper::generateUuid()));
        $rejectionReason = new RejectionReason(RejectionReasonCode::OTHER, 'missing information');

        $input = new RejectAccountCategoryChangeRequestInput($requestIdentifier, $principal, $rejectionReason);

        $this->assertSame($requestIdentifier, $input->requestIdentifier());
        $this->assertSame($principal, $input->principal());
        $this->assertSame($rejectionReason, $input->rejectionReason());
    }
}
