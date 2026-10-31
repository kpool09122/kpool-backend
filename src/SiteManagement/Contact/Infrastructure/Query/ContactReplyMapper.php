<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Infrastructure\Query;

use Application\Models\SiteManagement\ContactReply;
use DateTimeInterface;

final class ContactReplyMapper
{
    /** @return array{replyIdentifier: string, replyNumber: int, principalIdentifier: ?string, name: ?string, content: string, sentAt: ?string, createdAt: string} */
    public static function toArray(ContactReply $reply): array
    {
        return [
            'replyIdentifier' => (string) $reply->id,
            'content' => (string) $reply->content,
            'sentAt' => $reply->sent_at?->format(DateTimeInterface::ATOM),
            'replyNumber' => $reply->reply_number,
            'createdAt' => $reply->created_at->format(DateTimeInterface::ATOM),
            'principalIdentifier' => $reply->principal_identifier,
            'name' => $reply->principal?->identity?->identity_name,
        ];
    }
}
