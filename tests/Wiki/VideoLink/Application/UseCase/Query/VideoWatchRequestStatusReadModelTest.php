<?php

declare(strict_types=1);

namespace Tests\Wiki\VideoLink\Application\UseCase\Query;

use PHPUnit\Framework\TestCase;
use Source\Wiki\VideoLink\Application\UseCase\Query\VideoWatchRequestStatusReadModel;

class VideoWatchRequestStatusReadModelTest extends TestCase
{
    public function testSerializesOnlyPersonalStatusAndRestrictionFields(): void
    {
        $model = new VideoWatchRequestStatusReadModel('video', false, true, null, null, null, 3, null, null);
        self::assertSame(['videoLinkIdentifier' => 'video', 'sent' => false, 'canSend' => true, 'reason' => null, 'lastSentAt' => null, 'nextSendAt' => null, 'remainingRequests' => 3, 'unlikeRestrictedUntil' => null, 'deleteRestrictedUntil' => null], $model->toArray());
        $model = new VideoWatchRequestStatusReadModel('video', true, false, 'resend_not_available', 'sent', 'next', 0, 'own', 'all');
        self::assertSame(['videoLinkIdentifier' => 'video', 'sent' => true, 'canSend' => false, 'reason' => 'resend_not_available', 'lastSentAt' => 'sent', 'nextSendAt' => 'next', 'remainingRequests' => 0, 'unlikeRestrictedUntil' => 'own', 'deleteRestrictedUntil' => 'all'], $model->toArray());
    }
}
