<?php

declare(strict_types=1);

namespace Tests\Identity\Application\EventHandler;

use Mockery;
use Source\Account\Affiliation\Domain\Event\AffiliationRequested;
use Source\Account\Shared\Domain\ValueObject\AffiliationIdentifier;
use Source\Identity\Application\EventHandler\AffiliationRequestedHandler;
use Source\Identity\Application\Service\AffiliationRequestNotificationServiceInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\Email;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class AffiliationRequestedHandlerTest extends TestCase
{
    public function testHandleSendsAffiliationRequestNotification(): void
    {
        $targetEmail = new Email('target@example.com');
        /** @var AffiliationRequestNotificationServiceInterface&\Mockery\MockInterface $service */
        $service = Mockery::mock(AffiliationRequestNotificationServiceInterface::class);
        $service->shouldReceive('sendAffiliationRequestNotification')->once()->with($targetEmail);

        $handler = new AffiliationRequestedHandler($service);
        $handler->handle(new AffiliationRequested(
            new AffiliationIdentifier(StrTestHelper::generateUuid()),
            $targetEmail,
            new AccountIdentifier(StrTestHelper::generateUuid()),
            new AccountIdentifier(StrTestHelper::generateUuid()),
            new AccountIdentifier(StrTestHelper::generateUuid()),
        ));
    }
}
