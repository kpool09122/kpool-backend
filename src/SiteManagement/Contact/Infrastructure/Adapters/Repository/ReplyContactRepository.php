<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Infrastructure\Adapters\Repository;

use Application\Models\SiteManagement\ContactReply as ContactReplyModel;
use Source\Shared\Application\Service\Encryption\EncryptionServiceInterface;
use Source\Shared\Domain\ValueObject\Email;
use Source\SiteManagement\Contact\Domain\Entity\ReplyContact as ReplyContactEntity;
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

    public function save(ReplyContactEntity $replyContact): void
    {
        ContactReplyModel::query()->updateOrCreate(
            [
                'id' => (string)$replyContact->replyIdentifier(),
            ],
            [
                'contact_id' => (string)$replyContact->contactIdentifier(),
                'reply_number' => $replyContact->replyNumber(),
                'principal_identifier' => $replyContact->principalIdentifier() !== null
                    ? (string)$replyContact->principalIdentifier()
                    : null,
                'to_email' => $this->encryptionService->encrypt((string)$replyContact->toEmail()),
                'content' => (string)$replyContact->content(),
                'sent_at' => $replyContact->sentAt(),
                'failed_at' => $replyContact->failedAt(),
            ]
        );
    }

    public function findById(ContactReplyIdentifier $contactReplyIdentifier): ?ReplyContactEntity
    {
        $model = ContactReplyModel::query()
            ->whereKey((string)$contactReplyIdentifier)
            ->first();
        if ($model === null) {
            return null;
        }

        return new ReplyContactEntity(
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
