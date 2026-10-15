<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Application\EventHandler;

use Application\Providers\Wiki\EventServiceProvider;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Events\Dispatcher as LaravelEventDispatcher;
use Mockery;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Wiki\Application\Service\WikiWorkflowNotificationServiceInterface;
use Source\Wiki\Wiki\Domain\Event\WikiApproved;
use Source\Wiki\Wiki\Domain\Event\WikiPublished;
use Source\Wiki\Wiki\Domain\Event\WikiRejected;
use Source\Wiki\Wiki\Domain\ValueObject\DraftWikiIdentifier;
use Source\Wiki\Wiki\Domain\ValueObject\DraftWikiRejectionReason;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class WikiWorkflowNotificationHandlerTest extends TestCase
{
    public function testRegisteredListenersForwardAllThreeWorkflowEvents(): void
    {
        /** @var WikiWorkflowNotificationServiceInterface&Mockery\MockInterface $wikiWorkflowNotificationService */
        $wikiWorkflowNotificationService = Mockery::mock(WikiWorkflowNotificationServiceInterface::class);
        $this->app()->instance(WikiWorkflowNotificationServiceInterface::class, $wikiWorkflowNotificationService);
        $dispatcher = new LaravelEventDispatcher($this->app());
        $this->app()->instance(Dispatcher::class, $dispatcher);
        (new EventServiceProvider($this->app()))->boot();
        $id = new DraftWikiIdentifier(StrTestHelper::generateUuid());
        foreach ([
            new WikiApproved($id, 'Example', ResourceType::GROUP),
            new WikiRejected($id, 'Example', ResourceType::GROUP, new DraftWikiRejectionReason('Needs sources')),
            new WikiPublished($id, 'Example', ResourceType::GROUP, new Slug('gr-example'), Language::KOREAN),
        ] as $event) {
            $wikiWorkflowNotificationService->shouldReceive('notify')->once()->with($event);
            $dispatcher->dispatch($event);
        }
    }
}
