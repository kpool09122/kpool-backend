<?php

declare(strict_types=1);

namespace Tests\Monetization\Account\Application\UseCase\Command\OnboardSeller;

use Source\Monetization\Account\Application\UseCase\Command\OnboardSeller\OnboardSellerOutput;
use Tests\TestCase;

class OnboardSellerOutputTest extends TestCase
{
    /**
     * 正常系: オンボーディングURLがセットされている場合、toArrayが正しい値を返すこと
     */
    public function testToArrayWithOnboardingUrl(): void
    {
        $expectedUrl = 'https://connect.stripe.com/setup/onboarding/1234';

        $output = new OnboardSellerOutput();
        $output->setOnboardingUrl($expectedUrl);

        $result = $output->toArray();

        $this->assertSame($expectedUrl, $result['onboardingUrl']);
    }

    /**
     * 正常系: オンボーディングURLがセットされていない場合、toArrayがnullの配列を返すこと
     */
    public function testToArrayWithoutOnboardingUrl(): void
    {
        $output = new OnboardSellerOutput();

        $result = $output->toArray();

        $this->assertSame([
            'onboardingUrl' => null,
        ], $result);
    }
}
