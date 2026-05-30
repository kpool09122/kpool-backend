<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Domain\Factory;

use DateTimeImmutable;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\SiteManagement\Contact\Domain\Entity\ReplyCotact;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactIdentifier;
use Source\SiteManagement\Contact\Domain\ValueObject\ReplyContent;

interface ReplyContactFactoryInterface
{
    public function create(
        ContactIdentifier $contactIdentifier,
        ?IdentityIdentifier $identityIdentifier,
        Email $toEmail,
        ReplyContent $content,
        ?DateTimeImmutable $sentAt,
        ?DateTimeImmutable $failedAt,
    ): ReplyCotact;
}
