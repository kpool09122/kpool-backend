<?php

namespace Businesses\SiteManagement\Contact\Domain\Entity;

use Businesses\Shared\ValueObject\Email;
use Businesses\SiteManagement\Contact\Domain\ValueObject\Category;
use Businesses\SiteManagement\Contact\Domain\ValueObject\ContactIdentifier;
use Businesses\SiteManagement\Contact\Domain\ValueObject\ContactName;
use Businesses\SiteManagement\Contact\Domain\ValueObject\Content;

readonly class Contact
{
    public function __construct(
        private ContactIdentifier $contactIdentifier,
        private Category $category,
        private ContactName $name,
        private Email $email,
        private Content $content,
    ) {
    }

    public function contactIdentifier(): ContactIdentifier
    {
        return $this->contactIdentifier;
    }

    public function category(): Category
    {
        return $this->category;
    }

    public function name(): ContactName
    {
        return $this->name;
    }

    public function email(): Email
    {
        return $this->email;
    }

    public function content(): Content
    {
        return $this->content;
    }
}
