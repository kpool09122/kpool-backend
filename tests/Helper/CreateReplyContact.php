<?php

declare(strict_types=1);

namespace Tests\Helper;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Source\Shared\Application\Service\Encryption\EncryptionServiceInterface;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\SiteManagement\Contact\Domain\Entity\ReplyCotact;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactIdentifier;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactReplyIdentifier;
use Source\SiteManagement\Contact\Domain\ValueObject\ReplyContent;

class CreateReplyContact
{
    public static function create(
        ContactIdentifier $contactIdentifier,
        Email $toEmail,
        ?IdentityIdentifier $identityIdentifier,
        ?DateTimeImmutable $sentAt,
        ?DateTimeImmutable $failedAt,
        DateTimeImmutable $createdAt,
        string $content,
        EncryptionServiceInterface $encryptionService
    ): ReplyCotact {
        $reply = new ReplyCotact(
            new ContactReplyIdentifier(StrTestHelper::generateUuid()),
            $contactIdentifier,
            $identityIdentifier,
            $toEmail,
            new ReplyContent($content),
            $sentAt,
            $failedAt,
            $createdAt,
        );

        DB::table('contact_replies')->insert([
            'id' => (string) $reply->replyIdentifier(),
            'contact_id' => (string) $reply->contactIdentifier(),
            'identity_identifier' => $reply->identityIdentifier() !== null ? (string) $reply->identityIdentifier() : null,
            'to_email' => $encryptionService->encrypt((string) $reply->toEmail()),
            'content' => (string) $reply->content(),
            'sent_at' => $reply->sentAt()?->format('Y-m-d H:i:s'),
            'failed_at' => $reply->failedAt()?->format('Y-m-d H:i:s'),
            'created_at' => $reply->createdAt()->format('Y-m-d H:i:s'),
            'updated_at' => $reply->createdAt()->format('Y-m-d H:i:s'),
        ]);

        return $reply;
    }
}
