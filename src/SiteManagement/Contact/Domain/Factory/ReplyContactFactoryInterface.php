<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Domain\Factory;

use DateTimeImmutable;
use Source\Shared\Domain\ValueObject\Email;
use Source\SiteManagement\Contact\Domain\Entity\ReplyCotact;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactIdentifier;
use Source\SiteManagement\Contact\Domain\ValueObject\ReplyContent;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;

interface ReplyContactFactoryInterface
{
    public function create(
        ContactIdentifier $contactIdentifier,
        ?PrincipalIdentifier $principalIdentifier,
        Email $toEmail,
        ReplyContent $content,
        ?DateTimeImmutable $sentAt,
        ?DateTimeImmutable $failedAt,
    ): ReplyCotact;
}
