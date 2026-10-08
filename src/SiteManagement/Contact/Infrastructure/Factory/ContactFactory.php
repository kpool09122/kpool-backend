<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Infrastructure\Factory;

use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\Language;
use Source\SiteManagement\Contact\Domain\Entity\Contact;
use Source\SiteManagement\Contact\Domain\Factory\ContactFactoryInterface;
use Source\SiteManagement\Contact\Domain\ValueObject\Category;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactIdentifier;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactName;
use Source\SiteManagement\Contact\Domain\ValueObject\Content;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;

readonly class ContactFactory implements ContactFactoryInterface
{
    public function __construct(
        private UuidGeneratorInterface $generator,
    ) {
    }

    public function create(
        Category $category,
        ContactName $contactName,
        Email $email,
        Content $content,
        ?PrincipalIdentifier $principalIdentifier,
        Language $language,
    ): Contact {
        return new Contact(
            new ContactIdentifier($this->generator->generate()),
            $principalIdentifier,
            $category,
            $contactName,
            $email,
            $content,
            $language,
        );
    }
}
