<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Infrastructure\Adapters\Repository;

use Application\Models\SiteManagement\ContactReply as ContactReplyModel;
use Source\Shared\Application\Service\Encryption\EncryptionServiceInterface;
use Source\Shared\Domain\ValueObject\Email;
use Source\SiteManagement\Contact\Domain\Entity\Reply;
use Source\SiteManagement\Contact\Domain\Repository\ReplyContactRepositoryInterface;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactIdentifier;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactReplyIdentifier;
use Source\SiteManagement\Contact\Domain\ValueObject\ReplyContent;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;

final class ReplyContactRepository implements ReplyContactRepositoryInterface
{
    public function __construct(
        private readonly EncryptionServiceInterface $encryptionService,
    ) {
    }

    public function save(Reply $reply): void
    {
        ContactReplyModel::query()->updateOrCreate(
            [
                'id' => (string)$reply->replyIdentifier(),
            ],
            [
                'contact_id' => (string)$reply->contactIdentifier(),
                'reply_number' => $reply->replyNumber(),
                'principal_identifier' => $reply->principalIdentifier() !== null
                    ? (string)$reply->principalIdentifier()
                    : null,
                'to_email' => $this->encryptionService->encrypt((string)$reply->toEmail()),
                'content' => (string)$reply->content(),
                'sent_at' => $reply->sentAt(),
                'failed_at' => $reply->failedAt(),
            ]
        );
    }

    public function findById(ContactReplyIdentifier $contactReplyIdentifier): ?Reply
    {
        $model = ContactReplyModel::query()
            ->whereKey((string)$contactReplyIdentifier)
            ->first();
        if ($model === null) {
            return null;
        }

        return new Reply(
            new ContactReplyIdentifier((string)$model->id),
            new ContactIdentifier((string)$model->contact_id),
            $model->principal_identifier !== null
                ? new PrincipalIdentifier((string)$model->principal_identifier)
                : null,
            new Email($this->encryptionService->decrypt((string)$model->to_email)),
            new ReplyContent((string)$model->content),
            $model->sent_at?->toDateTimeImmutable(),
            $model->failed_at?->toDateTimeImmutable(),
            $model->created_at->toDateTimeImmutable(),
            $model->reply_number,
        );
    }
}
