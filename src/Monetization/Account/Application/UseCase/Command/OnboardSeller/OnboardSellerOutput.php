<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Application\UseCase\Command\OnboardSeller;

class OnboardSellerOutput implements OnboardSellerOutputPort
{
    private ?string $onboardingUrl = null;

    public function setOnboardingUrl(string $onboardingUrl): void
    {
        $this->onboardingUrl = $onboardingUrl;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'onboardingUrl' => $this->onboardingUrl,
        ];
    }
}
