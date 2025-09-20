<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Domain\Factory;

use Source\Shared\Domain\ValueObject\Email;
use Source\SiteManagement\Contact\Domain\Entity\Contact;
use Source\SiteManagement\Contact\Domain\ValueObject\Category;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactName;
use Source\SiteManagement\Contact\Domain\ValueObject\Content;

interface ContactFactoryInterface
{
    public function create(
        Category $category,
        ContactName $contactName,
        Email $email,
        Content $content,
    ): Contact;
}
