<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Domain\Entity;

use DateTimeImmutable;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactIdentifier;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactReplyIdentifier;
use Source\SiteManagement\Contact\Domain\ValueObject\ReplyContent;

readonly class ReplyCotact
{
    public function __construct(
        private ContactReplyIdentifier $replyIdentifier,
        private ContactIdentifier $contactIdentifier,
        private ?IdentityIdentifier $identityIdentifier,
        private Email $toEmail,
        private ReplyContent $content,
        private ?DateTimeImmutable $sentAt,
        private ?DateTimeImmutable $failedAt,
        private DateTimeImmutable $createdAt,
    ) {
    }

    public function replyIdentifier(): ContactReplyIdentifier
    {
        return $this->replyIdentifier;
    }

    public function contactIdentifier(): ContactIdentifier
    {
        return $this->contactIdentifier;
    }

    public function identityIdentifier(): ?IdentityIdentifier
    {
        return $this->identityIdentifier;
    }

    public function toEmail(): Email
    {
        return $this->toEmail;
    }

    public function content(): ReplyContent
    {
        return $this->content;
    }

    public function sentAt(): ?DateTimeImmutable
    {
        return $this->sentAt;
    }

    public function failedAt(): ?DateTimeImmutable
    {
        return $this->failedAt;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
