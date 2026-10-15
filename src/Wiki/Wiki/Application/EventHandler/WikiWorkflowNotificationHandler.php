<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\EventHandler;

use Source\Wiki\Wiki\Application\Service\WikiWorkflowNotificationServiceInterface;
use Source\Wiki\Wiki\Domain\Event\WikiApproved;
use Source\Wiki\Wiki\Domain\Event\WikiPublished;
use Source\Wiki\Wiki\Domain\Event\WikiRejected;

readonly class WikiWorkflowNotificationHandler
{
    public function __construct(private WikiWorkflowNotificationServiceInterface $wikiWorkflowNotificationService)
    {
    }

    public function handle(WikiApproved|WikiRejected|WikiPublished $event): void
    {
        $this->wikiWorkflowNotificationService->notify($event);
    }
}
