<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Application\UseCase\Command\OnboardSeller;

use Source\Monetization\Account\Domain\Exception\CapabilityAlreadyGrantedException;
use Source\Monetization\Account\Domain\Exception\MonetizationAccountNotFoundException;

interface OnboardSellerInterface
{
    /**
     * @param OnboardSellerInputPort $input
     * @return string
     * @throws MonetizationAccountNotFoundException
     * @throws CapabilityAlreadyGrantedException
     */
    public function process(OnboardSellerInputPort $input): string;
}
