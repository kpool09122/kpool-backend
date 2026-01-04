<?php

declare(strict_types=1);

namespace Tests\Account\Application\UseCase\Command\TerminateAffiliation;

use PHPUnit\Framework\TestCase;
use Source\Account\Application\UseCase\Command\TerminateAffiliation\TerminateAffiliationInput;
use Source\Account\Domain\ValueObject\AffiliationIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Tests\Helper\StrTestHelper;

class TerminateAffiliationInputTest extends TestCase
{
    /**
     * 正常系: インスタンスが正しく作成できること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $affiliationIdentifier = new AffiliationIdentifier(StrTestHelper::generateUuid());
        $terminatorAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());

        $input = new TerminateAffiliationInput(
            $affiliationIdentifier,
            $terminatorAccountIdentifier,
        );

        $this->assertSame($affiliationIdentifier, $input->affiliationIdentifier());
        $this->assertSame($terminatorAccountIdentifier, $input->terminatorAccountIdentifier());
    }
}
