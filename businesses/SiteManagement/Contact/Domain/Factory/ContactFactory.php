<?php

namespace Businesses\SiteManagement\Contact\Domain\Factory;

use Businesses\Shared\Service\Ulid\UlidGeneratorInterface;
use Businesses\Shared\ValueObject\Email;
use Businesses\SiteManagement\Contact\Domain\Entity\Contact;
use Businesses\SiteManagement\Contact\Domain\ValueObject\Category;
use Businesses\SiteManagement\Contact\Domain\ValueObject\ContactIdentifier;
use Businesses\SiteManagement\Contact\Domain\ValueObject\ContactName;
use Businesses\SiteManagement\Contact\Domain\ValueObject\Content;

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
