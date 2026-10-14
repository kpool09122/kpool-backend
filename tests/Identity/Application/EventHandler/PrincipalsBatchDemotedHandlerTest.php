<?php

declare(strict_types=1);

namespace Tests\Identity\Application\EventHandler;

use Mockery;
use Source\Identity\Application\EventHandler\PrincipalsBatchDemotedHandler;
use Source\Identity\Application\Service\CollaboratorNotificationServiceInterface;
use Source\Shared\Application\Service\AccountNotificationRecipient;
use Source\Shared\Application\Service\AccountNotificationRecipientServiceInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Principal\Domain\Event\PrincipalsBatchDemoted;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class PrincipalsBatchDemotedHandlerTest extends TestCase
{
    public function testHandleUsesOnlyEventAccountsAndCurrentRecipients(): void
    {
        $account = new AccountIdentifier(StrTestHelper::generateUuid());
        $recipient = new AccountNotificationRecipient(new Email('configured@example.com'), Language::ENGLISH);
        /** @var AccountNotificationRecipientServiceInterface&Mockery\MockInterface $resolver */
        $resolver = Mockery::mock(AccountNotificationRecipientServiceInterface::class);
        $resolver->shouldReceive('resolve')->once()->with([$account])->andReturn([$recipient]);
        /** @var CollaboratorNotificationServiceInterface&Mockery\MockInterface $service */
        $service = Mockery::mock(CollaboratorNotificationServiceInterface::class);
        $service->shouldReceive('sendDemotionNotification')->once()->with($recipient->email(), $recipient->language());
        $this->app()->instance(AccountNotificationRecipientServiceInterface::class, $resolver);
        $this->app()->instance(CollaboratorNotificationServiceInterface::class, $service);
        $this->app()->make(PrincipalsBatchDemotedHandler::class)->handle(new PrincipalsBatchDemoted([$account]));
    }

    public function testNoRecipientsMeansNoNotification(): void
    {
        $account = new AccountIdentifier(StrTestHelper::generateUuid());
        /** @var AccountNotificationRecipientServiceInterface&Mockery\MockInterface $resolver */
        $resolver = Mockery::mock(AccountNotificationRecipientServiceInterface::class);
        $resolver->shouldReceive('resolve')->once()->with([$account])->andReturn([]);
        /** @var CollaboratorNotificationServiceInterface&Mockery\MockInterface $service */
        $service = Mockery::mock(CollaboratorNotificationServiceInterface::class);
        $service->shouldNotReceive('sendDemotionNotification');
        (new PrincipalsBatchDemotedHandler($resolver, $service))->handle(new PrincipalsBatchDemoted([$account]));
    }
}
