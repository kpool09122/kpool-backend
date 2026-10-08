<?php

declare(strict_types=1);

namespace Tests\Helper;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Source\Shared\Application\Service\Encryption\EncryptionServiceInterface;
use Source\Shared\Domain\ValueObject\Email;
use Source\SiteManagement\Contact\Domain\Entity\ReplyCotact;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactIdentifier;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactReplyIdentifier;
use Source\SiteManagement\Contact\Domain\ValueObject\ReplyContent;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;

class CreateReplyContact
{
    public static function create(
        ContactIdentifier $contactIdentifier,
        Email $toEmail,
        ?PrincipalIdentifier $principalIdentifier,
        ?DateTimeImmutable $sentAt,
        ?DateTimeImmutable $failedAt,
        DateTimeImmutable $createdAt,
        string $content,
        EncryptionServiceInterface $encryptionService
    ): ReplyCotact {
        $reply = new ReplyCotact(
            new ContactReplyIdentifier(StrTestHelper::generateUuid()),
            $contactIdentifier,
            $principalIdentifier,
            $toEmail,
            new ReplyContent($content),
            $sentAt,
            $failedAt,
            $createdAt,
        );

        DB::table('contact_replies')->insert([
            'id' => (string) $reply->replyIdentifier(),
            'contact_id' => (string) $reply->contactIdentifier(),
            'principal_identifier' => $reply->principalIdentifier() !== null ? (string) $reply->principalIdentifier() : null,
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
