<?php

declare(strict_types=1);

namespace Tests\Account\Affiliation\Application\UseCase\Command\ApproveAffiliation;

use PHPUnit\Framework\TestCase;
use Source\Account\Affiliation\Application\UseCase\Command\ApproveAffiliation\ApproveAffiliationInput;
use Source\Account\Shared\Domain\ValueObject\AffiliationIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Tests\Helper\StrTestHelper;

class ApproveAffiliationInputTest extends TestCase
{
    /**
     * 正常系: インスタンスが正しく作成できること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $affiliationIdentifier = new AffiliationIdentifier(StrTestHelper::generateUuid());
        $approverAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());

        $input = new ApproveAffiliationInput(
            $affiliationIdentifier,
            $approverAccountIdentifier,
        );

        $this->assertSame($affiliationIdentifier, $input->affiliationIdentifier());
        $this->assertSame($approverAccountIdentifier, $input->approverAccountIdentifier());
    }
}
