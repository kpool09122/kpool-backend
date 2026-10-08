<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Application\UseCase\Command\SubmitContact;

use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\Language;
use Source\SiteManagement\Contact\Domain\ValueObject\Category;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactName;
use Source\SiteManagement\Contact\Domain\ValueObject\Content;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;

readonly class SubmitContactInput implements SubmitContactInputPort
{
    public function __construct(
        private ?PrincipalIdentifier $principalIdentifier,
        private Category $category,
        private ContactName $name,
        private Email $email,
        private Content $content,
        private Language $language,
    ) {
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
