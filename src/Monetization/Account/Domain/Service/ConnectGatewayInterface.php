<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Domain\Service;

use Source\Monetization\Account\Domain\ValueObject\ConnectAccountStatus;
use Source\Monetization\Account\Domain\ValueObject\ConnectedAccountId;
use Source\Monetization\Account\Infrastructure\Exception\StripeConnectException;
use Source\Shared\Domain\ValueObject\CountryCode;
use Source\Shared\Domain\ValueObject\Email;

interface ConnectGatewayInterface
{
    /**
     * 販売者用のStripe Connected Accountを作成
     *
     * @param Email $email
     * @param CountryCode $countryCode
     * @return ConnectedAccountId
     * @throws StripeConnectException
     */
    public function createConnectedAccount(Email $email, CountryCode $countryCode): ConnectedAccountId;

    /**
     * オンボーディング用のAccount Linkを生成
     *
     * @param ConnectedAccountId $accountId
     * @param string $refreshUrl
     * @param string $returnUrl
     * @return string
     * @throws StripeConnectException
     */
    public function createAccountLink(
        ConnectedAccountId $accountId,
        string             $refreshUrl,
        string             $returnUrl
    ): string;

    /**
     * アカウントの検証状態を取得
     *
     * @param ConnectedAccountId $accountId
     * @return ConnectAccountStatus
     * @throws StripeConnectException
     */
    public function getAccountStatus(ConnectedAccountId $accountId): ConnectAccountStatus;
}
