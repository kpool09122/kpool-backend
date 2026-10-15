<?php

declare(strict_types=1);

namespace Tests\Account\Notification\Application\EventHandler;

use Application\Providers\Account\EventServiceProvider;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Events\Dispatcher as LaravelEventDispatcher;
use Mockery;
use Source\Account\Affiliation\Domain\Event\AffiliationApproved;
use Source\Account\Delegation\Domain\Event\DelegationApproved;
use Source\Account\Notification\Application\Service\AccountApprovalNotificationServiceInterface;
use Source\Account\Shared\Domain\ValueObject\AffiliationIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class AccountRequestApprovedHandlerTest extends TestCase
{
    public function testRegisteredListenersNotifyOnlyRequesterInBothRequestDirections(): void
    {
        $agency = new AccountIdentifier(StrTestHelper::generateUuid());
        $talent = new AccountIdentifier(StrTestHelper::generateUuid());
        /** @var AccountApprovalNotificationServiceInterface&Mockery\MockInterface $accountApprovalNotificationService */
        $accountApprovalNotificationService = Mockery::mock(AccountApprovalNotificationServiceInterface::class);
        $this->app()->instance(AccountApprovalNotificationServiceInterface::class, $accountApprovalNotificationService);
        $dispatcher = new LaravelEventDispatcher($this->app());
        $this->app()->instance(Dispatcher::class, $dispatcher);
        (new EventServiceProvider($this->app()))->boot();
        foreach ([[$agency, $talent], [$talent, $agency]] as [$requester, $approver]) {
            $accountApprovalNotificationService->shouldReceive('sendAffiliationApproval')->once()->with($requester, 'Approver');
            $accountApprovalNotificationService->shouldReceive('sendDelegationApproval')->once()->with($requester, 'Approver');
            $dispatcher->dispatch(new AffiliationApproved(new AffiliationIdentifier(StrTestHelper::generateUuid()), $requester, $approver, 'Approver'));
            $dispatcher->dispatch(new DelegationApproved(new DelegationIdentifier(StrTestHelper::generateUuid()), $requester, $approver, 'Approver'));
        }
    }
}
