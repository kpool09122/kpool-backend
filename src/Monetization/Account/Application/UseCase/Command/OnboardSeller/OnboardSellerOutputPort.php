<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Application\UseCase\Command\OnboardSeller;

interface OnboardSellerOutputPort
{
    public function setOnboardingUrl(string $onboardingUrl): void;

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
