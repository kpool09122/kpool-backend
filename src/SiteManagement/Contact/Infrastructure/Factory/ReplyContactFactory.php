<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Infrastructure\Factory;

use DateTimeImmutable;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\SiteManagement\Contact\Domain\Entity\ReplyCotact;
use Source\SiteManagement\Contact\Domain\Factory\ReplyContactFactoryInterface;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactIdentifier;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactReplyIdentifier;
use Source\SiteManagement\Contact\Domain\ValueObject\ReplyContent;
use Source\SiteManagement\Contact\Domain\ValueObject\ReplyStatus;

readonly class ReplyContactFactory implements ReplyContactFactoryInterface
{
    public function __construct(
        private UuidGeneratorInterface $generator,
    ) {
    }

    public function create(
        ContactIdentifier $contactIdentifier,
        ?IdentityIdentifier $identityIdentifier,
        Email $toEmail,
        ReplyContent $content,
        ReplyStatus $status,
        ?DateTimeImmutable $sentAt,
    ): ReplyCotact {
        return new ReplyCotact(
            new ContactReplyIdentifier($this->generator->generate()),
            $contactIdentifier,
            $identityIdentifier,
            $toEmail,
            $content,
            $status,
            $sentAt,
            new DateTimeImmutable('now'),
        );
    }
}
