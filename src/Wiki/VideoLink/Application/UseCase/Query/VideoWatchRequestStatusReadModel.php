<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Application\UseCase\Query;

final readonly class VideoWatchRequestStatusReadModel
{
    public function __construct(
        public string $videoLinkIdentifier,
        public bool $sent,
        public bool $canSend,
        public ?string $reason,
        public ?string $lastSentAt,
        public ?string $nextSendAt,
        public int $remainingRequests,
        public ?string $unlikeRestrictedUntil,
        public ?string $deleteRestrictedUntil,
    ) {
    }

    /** @return array{videoLinkIdentifier:string,sent:bool,canSend:bool,reason:?string,lastSentAt:?string,nextSendAt:?string,remainingRequests:int,unlikeRestrictedUntil:?string,deleteRestrictedUntil:?string} */
    public function toArray(): array
    {
        return ['videoLinkIdentifier' => $this->videoLinkIdentifier, 'sent' => $this->sent, 'canSend' => $this->canSend, 'reason' => $this->reason, 'lastSentAt' => $this->lastSentAt, 'nextSendAt' => $this->nextSendAt, 'remainingRequests' => $this->remainingRequests, 'unlikeRestrictedUntil' => $this->unlikeRestrictedUntil, 'deleteRestrictedUntil' => $this->deleteRestrictedUntil];
    }
}
