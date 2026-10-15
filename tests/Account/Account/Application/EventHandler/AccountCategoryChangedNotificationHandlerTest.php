<?php

declare(strict_types=1);

namespace Tests\Account\Account\Application\EventHandler;

use Application\Providers\Account\EventServiceProvider;
use DateTimeImmutable;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Events\Dispatcher as LaravelEventDispatcher;
use Mockery;
use Source\Account\Account\Application\Service\AccountCategoryChangeNotificationServiceInterface;
use Source\Account\Account\Domain\Event\AccountCategoryChanged;
use Source\Account\Shared\Domain\ValueObject\AccountType;
use Source\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class AccountCategoryChangedNotificationHandlerTest extends TestCase
{
    public function testRegisteredListenerNotifiesApprovedAccountRatherThanReviewer(): void
    {
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $reviewerAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $event = new AccountCategoryChanged($accountIdentifier, AccountCategory::GENERAL, AccountCategory::TALENT, $reviewerAccountIdentifier, new DateTimeImmutable(), AccountType::INDIVIDUAL);

        /** @var AccountCategoryChangeNotificationServiceInterface&Mockery\MockInterface $accountCategoryChangeNotificationService */
        $accountCategoryChangeNotificationService = Mockery::mock(AccountCategoryChangeNotificationServiceInterface::class);
        $accountCategoryChangeNotificationService->shouldReceive('sendApprovalNotification')->once()->with($accountIdentifier, AccountCategory::TALENT);
        $this->app()->instance(AccountCategoryChangeNotificationServiceInterface::class, $accountCategoryChangeNotificationService);

        $dispatcher = new LaravelEventDispatcher($this->app());
        $this->app()->instance(Dispatcher::class, $dispatcher);
        (new EventServiceProvider($this->app()))->boot();

        $dispatcher->dispatch($event);
    }
}
