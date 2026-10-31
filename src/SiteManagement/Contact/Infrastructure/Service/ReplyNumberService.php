<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Infrastructure\Service;

use Application\Models\SiteManagement\ContactReply as ContactReplyModel;
use Source\SiteManagement\Contact\Domain\Service\ReplyNumberServiceInterface;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactIdentifier;

readonly class ReplyNumberService implements ReplyNumberServiceInterface
{
    public function next(ContactIdentifier $contactIdentifier): int
    {
        /** @var int|null $latestReplyNumber */
        $latestReplyNumber = ContactReplyModel::query()
            ->where('contact_id', (string) $contactIdentifier)
            ->max('reply_number');

        return ($latestReplyNumber ?? 0) + 1;
    }
}
