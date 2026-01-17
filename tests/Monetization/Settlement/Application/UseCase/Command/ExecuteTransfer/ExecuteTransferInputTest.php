<?php

declare(strict_types=1);

namespace Tests\Monetization\Settlement\Application\UseCase\Command\ExecuteTransfer;

use PHPUnit\Framework\TestCase;
use Source\Monetization\Settlement\Application\UseCase\Command\ExecuteTransfer\ExecuteTransferInput;
use Source\Monetization\Settlement\Domain\ValueObject\TransferIdentifier;
use Tests\Helper\StrTestHelper;

class ExecuteTransferInputTest extends TestCase
{
    /**
     * 正常系: 正しく送金処理を実行できること.
     *
     * @return void
     */
    public function test__construct(): void
    {
        $transferIdentifier = new TransferIdentifier(StrTestHelper::generateUuid());
        $input = new ExecuteTransferInput($transferIdentifier);
        $this->assertSame($transferIdentifier, $input->transferIdentifier());
    }
}
