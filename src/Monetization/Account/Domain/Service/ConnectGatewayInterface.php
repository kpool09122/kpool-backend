<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Domain\Service;

use Source\Monetization\Account\Domain\ValueObject\ConnectAccountStatus;
use Source\Monetization\Account\Domain\ValueObject\StripeConnectedAccountId;
use Source\Shared\Domain\ValueObject\CountryCode;
use Source\Shared\Domain\ValueObject\Email;

interface ConnectGatewayInterface
{
    /**
     * 販売者用のStripe Connected Accountを作成
     */
    public function createConnectedAccount(Email $email, CountryCode $countryCode): StripeConnectedAccountId;

    /**
     * オンボーディング用のAccount Linkを生成
     */
    public function createAccountLink(
        StripeConnectedAccountId $accountId,
        string $refreshUrl,
        string $returnUrl
    ): string;

    /**
     * アカウントの検証状態を取得
     */
    public function getAccountStatus(StripeConnectedAccountId $accountId): ConnectAccountStatus;
}
