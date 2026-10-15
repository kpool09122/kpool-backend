<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\Service;

use Source\Wiki\Wiki\Domain\Event\WikiApproved;
use Source\Wiki\Wiki\Domain\Event\WikiPublished;
use Source\Wiki\Wiki\Domain\Event\WikiRejected;

interface WikiWorkflowNotificationServiceInterface
{
    public function notify(WikiApproved|WikiRejected|WikiPublished $event): void;
}
