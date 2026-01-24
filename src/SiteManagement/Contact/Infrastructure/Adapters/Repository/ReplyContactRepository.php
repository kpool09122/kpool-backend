<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Infrastructure\Adapters\Repository;

use Application\Models\SiteManagement\ContactReply as ContactReplyModel;
use Source\Shared\Application\Service\Encryption\EncryptionServiceInterface;
use Source\SiteManagement\Contact\Domain\Entity\ReplyCotact;
use Source\SiteManagement\Contact\Domain\Repository\ReplyContactRepositoryInterface;

final class ReplyContactRepository implements ReplyContactRepositoryInterface
{
    public function __construct(
        private readonly EncryptionServiceInterface $encryptionService,
    ) {
    }

    public function save(ReplyCotact $replyCotact): void
    {
        ContactReplyModel::query()->updateOrCreate(
            [
                'id' => (string)$replyCotact->replyIdentifier(),
            ],
            [
                'contact_id' => (string)$replyCotact->contactIdentifier(),
                'identity_identifier' => $replyCotact->identityIdentifier() !== null
                    ? (string)$replyCotact->identityIdentifier()
                    : null,
                'to_email' => $this->encryptionService->encrypt((string)$replyCotact->toEmail()),
                'content' => (string)$replyCotact->content(),
                'status' => $replyCotact->status()->value,
                'sent_at' => $replyCotact->sentAt(),
            ]
        );
    }
}
