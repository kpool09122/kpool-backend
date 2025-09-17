<?php

declare(strict_types=1);

namespace Businesses\SiteManagement\Contact\Domain\Factory;

use Businesses\Shared\ValueObject\Email;
use Businesses\SiteManagement\Contact\Domain\Entity\Contact;
use Businesses\SiteManagement\Contact\Domain\ValueObject\Category;
use Businesses\SiteManagement\Contact\Domain\ValueObject\ContactName;
use Businesses\SiteManagement\Contact\Domain\ValueObject\Content;

interface ContactFactoryInterface
{
    public function create(
        Category $category,
        ContactName $contactName,
        Email $email,
        Content $content,
    ): Contact;
}
