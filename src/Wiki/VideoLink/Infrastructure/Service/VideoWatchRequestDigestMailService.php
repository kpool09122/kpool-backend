<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Infrastructure\Service;

use Application\Mail\VideoWatchRequestDigestMail;
use Illuminate\Support\Facades\Mail;
use Source\Shared\Application\Service\AccountNotificationRecipient;
use Source\Wiki\VideoLink\Application\Service\VideoWatchRequestDigest;
use Source\Wiki\VideoLink\Application\Service\VideoWatchRequestDigestMailServiceInterface;

final readonly class VideoWatchRequestDigestMailService implements VideoWatchRequestDigestMailServiceInterface
{
    public function send(AccountNotificationRecipient $recipient, VideoWatchRequestDigest $digest): void
    {
        $videosUrl = rtrim(config()->string('app.frontend_url'), '/') . '/' . rawurlencode($digest->wikiLanguage->value) . '/wiki/' . rawurlencode($digest->wikiSlug) . '?tab=video';
        Mail::to((string) $recipient->email())->send(new VideoWatchRequestDigestMail($digest->videos, $digest->recentRequestCount, $digest->newRequestCount, $videosUrl, $recipient->language()));
    }
}
