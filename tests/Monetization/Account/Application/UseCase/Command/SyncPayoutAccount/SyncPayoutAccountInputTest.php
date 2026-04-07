<?php

declare(strict_types=1);

namespace Tests\Monetization\Account\Application\UseCase\Command\SyncPayoutAccount;

use PHPUnit\Framework\TestCase;
use Source\Monetization\Account\Application\UseCase\Command\SyncPayoutAccount\SyncPayoutAccountInput;
use Source\Monetization\Account\Domain\ValueObject\AccountHolderType;
use Source\Monetization\Account\Domain\ValueObject\ConnectedAccountId;
use Source\Monetization\Account\Domain\ValueObject\ExternalAccountId;

class SyncPayoutAccountInputTest extends TestCase
{
    /**
     * 正常系: 全パラメータを指定して正しくインスタンスが作成できること.
     */
    public function test__constructWithAllParameters(): void
    {
        $connectedAccountId = new ConnectedAccountId('acct_1234567890');
        $externalAccountId = new ExternalAccountId('ba_1234567890');

        $input = new SyncPayoutAccountInput(
            connectedAccountId: $connectedAccountId,
            externalAccountId: $externalAccountId,
            eventType: 'account.external_account.created',
            bankName: 'MUFG Bank',
            last4: '1234',
            country: 'JP',
            currency: 'jpy',
            accountHolderType: AccountHolderType::INDIVIDUAL,
            isDefault: true,
        );

        $this->assertSame($connectedAccountId, $input->connectedAccountId());
        $this->assertSame($externalAccountId, $input->externalAccountId());
        $this->assertSame('account.external_account.created', $input->eventType());
        $this->assertSame('MUFG Bank', $input->bankName());
        $this->assertSame('1234', $input->last4());
        $this->assertSame('JP', $input->country());
        $this->assertSame('jpy', $input->currency());
        $this->assertSame(AccountHolderType::INDIVIDUAL, $input->accountHolderType());
        $this->assertTrue($input->isDefault());
    }

    /**
     * 正常系: オプショナルパラメータを省略して正しくインスタンスが作成できること.
     */
    public function test__constructWithRequiredParametersOnly(): void
    {
        $connectedAccountId = new ConnectedAccountId('acct_1234567890');
        $externalAccountId = new ExternalAccountId('ba_1234567890');

        $input = new SyncPayoutAccountInput(
            connectedAccountId: $connectedAccountId,
            externalAccountId: $externalAccountId,
            eventType: 'account.external_account.created',
        );

        $this->assertSame($connectedAccountId, $input->connectedAccountId());
        $this->assertSame($externalAccountId, $input->externalAccountId());
        $this->assertSame('account.external_account.created', $input->eventType());
        $this->assertNull($input->bankName());
        $this->assertNull($input->last4());
        $this->assertNull($input->country());
        $this->assertNull($input->currency());
        $this->assertNull($input->accountHolderType());
        $this->assertFalse($input->isDefault());
    }
}
