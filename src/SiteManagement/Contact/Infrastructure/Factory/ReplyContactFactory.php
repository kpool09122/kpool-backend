<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Infrastructure\Factory;

use DateTimeImmutable;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\ValueObject\Email;
use Source\SiteManagement\Contact\Domain\Entity\ReplyContact;
use Source\SiteManagement\Contact\Domain\Factory\ReplyContactFactoryInterface;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactIdentifier;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactReplyIdentifier;
use Source\SiteManagement\Contact\Domain\ValueObject\ReplyContent;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;

readonly class ReplyContactFactory implements ReplyContactFactoryInterface
{
    public function __construct(
        private UuidGeneratorInterface $generator,
    ) {
    }

    public function create(
        ContactIdentifier $contactIdentifier,
        ?PrincipalIdentifier $principalIdentifier,
        Email $toEmail,
        ReplyContent $content,
        ?DateTimeImmutable $sentAt,
        ?DateTimeImmutable $failedAt,
        int $replyNumber,
    ): ReplyContact {
        return new ReplyContact(
            new ContactReplyIdentifier($this->generator->generate()),
            $contactIdentifier,
            $principalIdentifier,
            $toEmail,
            $content,
            $sentAt,
            $failedAt,
            new DateTimeImmutable('now'),
            $replyNumber,
        );
    }
}
