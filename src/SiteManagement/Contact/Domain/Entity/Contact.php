<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Domain\Entity;

use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\Language;
use Source\SiteManagement\Contact\Domain\ValueObject\Category;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactIdentifier;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactName;
use Source\SiteManagement\Contact\Domain\ValueObject\Content;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;

readonly class Contact
{
    public function __construct(
        private ContactIdentifier $contactIdentifier,
        private ?PrincipalIdentifier $principalIdentifier,
        private Category $category,
        private ContactName $name,
        private Email $email,
        private Content $content,
        private Language $language,
    ) {
    }

    public function contactIdentifier(): ContactIdentifier
    {
        return $this->contactIdentifier;
    }

    public function principalIdentifier(): ?PrincipalIdentifier
    {
        return $this->principalIdentifier;
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

    public function language(): Language
    {
        return $this->language;
    }
}
