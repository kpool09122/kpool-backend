<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Application\Service;

use Source\Monetization\Account\Application\Exception\CardGatewayException;
use Source\Monetization\Account\Application\Exception\CardRegistrationRejectedException;
use Source\Monetization\Account\Domain\ValueObject\ExternalPaymentMethodId;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Account\Domain\ValueObject\PaymentCustomerId;
use Source\Monetization\Account\Domain\ValueObject\SetupIntentIdentifier;

interface CardGatewayInterface
{
    /** @throws CardGatewayException */
    public function createCustomer(MonetizationAccountIdentifier $accountIdentifier): PaymentCustomerId;

    /** @throws CardGatewayException */
    public function prepareRegistration(PaymentCustomerId $customerId): CardRegistrationPreparation;

    /**
     * @throws CardGatewayException
     * @throws CardRegistrationRejectedException
     */
    /**
     * @throws CardGatewayException
     * @throws CardRegistrationRejectedException
     */
    public function detach(PaymentCustomerId $customerId, ExternalPaymentMethodId $externalPaymentMethodId): void;

    /**
     * @throws CardGatewayException
     * @throws CardRegistrationRejectedException
     */
    public function verify(PaymentCustomerId $customerId, SetupIntentIdentifier $setupIntentIdentifier): VerifiedCard;
}
