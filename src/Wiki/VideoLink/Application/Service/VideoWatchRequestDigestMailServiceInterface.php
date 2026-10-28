<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Application\Service;

use Source\Shared\Application\Service\AccountNotificationRecipient;
use Throwable;

interface VideoWatchRequestDigestMailServiceInterface
{
    /** @throws Throwable */
    public function send(AccountNotificationRecipient $recipient, VideoWatchRequestDigest $digest): void;
}
