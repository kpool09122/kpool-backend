<?php

declare(strict_types=1);

namespace Tests\Identity\Application\EventHandler;

use Mockery;
use Source\Account\Affiliation\Domain\Event\AffiliationRequested;
use Source\Account\Shared\Domain\ValueObject\AffiliationIdentifier;
use Source\Identity\Application\EventHandler\AffiliationRequestedHandler;
use Source\Identity\Application\Service\AffiliationRequestNotificationServiceInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class AffiliationRequestedHandlerTest extends TestCase
{
    public function testSelectsReceivingAccountInBothRequestDirections(): void
    {
        $agency = new AccountIdentifier(StrTestHelper::generateUuid());
        $talent = new AccountIdentifier(StrTestHelper::generateUuid());
        foreach ([[$agency, $talent], [$talent, $agency]] as [$requester, $receiver]) {
            /** @var AffiliationRequestNotificationServiceInterface&Mockery\MockInterface $service */
            $service = Mockery::mock(AffiliationRequestNotificationServiceInterface::class);
            $service->shouldReceive('sendAffiliationRequestNotification')->once()->with(Mockery::on(static fn (AccountIdentifier $id): bool => (string) $id === (string) $receiver));
            (new AffiliationRequestedHandler($service))->handle(new AffiliationRequested(new AffiliationIdentifier(StrTestHelper::generateUuid()), $receiver, $agency, $talent, $requester));
        }
    }
}
