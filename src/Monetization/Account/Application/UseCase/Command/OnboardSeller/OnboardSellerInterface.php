<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Application\UseCase\Command\OnboardSeller;

use Source\Monetization\Account\Domain\Exception\CapabilityAlreadyGrantedException;
use Source\Monetization\Account\Domain\Exception\MonetizationAccountNotFoundException;
use Source\Monetization\Account\Infrastructure\Exception\StripeConnectException;

interface OnboardSellerInterface
{
    /**
     * @param OnboardSellerInputPort $input
     * @param OnboardSellerOutputPort $output
     * @return void
     * @throws MonetizationAccountNotFoundException
     * @throws CapabilityAlreadyGrantedException
     * @throws StripeConnectException
     */
    public function process(OnboardSellerInputPort $input, OnboardSellerOutputPort $output): void;
}
