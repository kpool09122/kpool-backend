<?php

declare(strict_types=1);

namespace Businesses\SiteManagement\Contact\UseCase\Command\SubmitContact;

use Businesses\Shared\ValueObject\Email;
use Businesses\SiteManagement\Contact\Domain\ValueObject\Category;
use Businesses\SiteManagement\Contact\Domain\ValueObject\ContactName;
use Businesses\SiteManagement\Contact\Domain\ValueObject\Content;

readonly class SubmitContactInput implements SubmitContactInputPort
{
    public function __construct(
        private Category $category,
        private ContactName $name,
        private Email $email,
        private Content $content,
    ) {
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
