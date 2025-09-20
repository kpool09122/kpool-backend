<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Domain\Factory;

use Source\Shared\Application\Service\Ulid\UlidGeneratorInterface;
use Source\Shared\Domain\ValueObject\Email;
use Source\SiteManagement\Contact\Domain\Entity\Contact;
use Source\SiteManagement\Contact\Domain\ValueObject\Category;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactIdentifier;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactName;
use Source\SiteManagement\Contact\Domain\ValueObject\Content;

class ContactFactory implements ContactFactoryInterface
{
    public function __construct(
        private UlidGeneratorInterface $ulidGenerator,
    ) {
    }

    public function create(
        Category $category,
        ContactName $contactName,
        Email $email,
        Content $content,
    ): Contact {
        return new Contact(
            new ContactIdentifier($this->ulidGenerator->generate()),
            $category,
            $contactName,
            $email,
            $content
        );
    }
}
