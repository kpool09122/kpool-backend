<?php

declare(strict_types=1);

namespace Tests\Account\Application\UseCase\Command\DeleteAccount;

use PHPUnit\Framework\TestCase;
use Source\Account\Application\UseCase\Command\DeleteAccount\DeleteAccountInput;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Tests\Helper\StrTestHelper;

class DeleteAccountInputTest extends TestCase
{
    /**
     * 正常系: 正しくインスタンスが作成できること.
     *
     * @return void
     */
    public function test__construct(): void
    {
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $input = new DeleteAccountInput($accountIdentifier);
        $this->assertSame($accountIdentifier, $input->accountIdentifier());
    }
}
