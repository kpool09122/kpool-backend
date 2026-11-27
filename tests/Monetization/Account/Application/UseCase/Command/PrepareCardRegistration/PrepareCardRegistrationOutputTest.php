<?php

declare(strict_types=1);

namespace Tests\Monetization\Account\Application\UseCase\Command\PrepareCardRegistration;

use LogicException;
use PHPUnit\Framework\TestCase;
use Source\Monetization\Account\Application\Service\CardRegistrationPreparation;
use Source\Monetization\Account\Application\UseCase\Command\PrepareCardRegistration\PrepareCardRegistrationOutput;
use Source\Monetization\Account\Domain\ValueObject\SetupIntentIdentifier;

class PrepareCardRegistrationOutputTest extends TestCase
{
    public function testSerializesSetupIntentForStripeJs(): void
    {
        $output = new PrepareCardRegistrationOutput();
        $output->setPreparation(new CardRegistrationPreparation(new SetupIntentIdentifier('seti_test123'), 'secret_test'));
        self::assertSame(['setupIntentIdentifier' => 'seti_test123','clientSecret' => 'secret_test'], $output->toArray());
    }

    public function testRejectsUninitializedOutput(): void
    {
        $this->expectException(LogicException::class);
        (new PrepareCardRegistrationOutput())->toArray();
    }
}
