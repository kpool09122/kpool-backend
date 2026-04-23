<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Infrastructure\Adapters\Repository;

use Application\Models\SiteManagement\ContactReply as ContactReplyModel;
use Source\Shared\Application\Service\Encryption\EncryptionServiceInterface;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\SiteManagement\Contact\Domain\Entity\ReplyCotact;
use Source\SiteManagement\Contact\Domain\Repository\ReplyContactRepositoryInterface;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactIdentifier;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactReplyIdentifier;
use Source\SiteManagement\Contact\Domain\ValueObject\ReplyContent;
use Source\SiteManagement\Contact\Domain\ValueObject\ReplyStatus;

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

    public function findById(ContactReplyIdentifier $contactReplyIdentifier): ?ReplyCotact
    {
        $model = ContactReplyModel::query()
            ->whereKey((string)$contactReplyIdentifier)
            ->first();
        if ($model === null) {
            return null;
        }

        return new ReplyCotact(
            new ContactReplyIdentifier((string)$model->id),
            new ContactIdentifier((string)$model->contact_id),
            $model->identity_identifier !== null
                ? new IdentityIdentifier((string)$model->identity_identifier)
                : null,
            new Email($this->encryptionService->decrypt((string)$model->to_email)),
            new ReplyContent((string)$model->content),
            ReplyStatus::from((int)$model->status),
            $model->sent_at?->toDateTimeImmutable(),
            $model->created_at->toDateTimeImmutable(),
        );
    }
}
