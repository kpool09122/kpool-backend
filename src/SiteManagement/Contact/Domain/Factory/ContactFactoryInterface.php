<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Domain\Factory;

use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\Language;
use Source\SiteManagement\Contact\Domain\Entity\Contact;
use Source\SiteManagement\Contact\Domain\ValueObject\Category;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactName;
use Source\SiteManagement\Contact\Domain\ValueObject\Content;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;

interface ContactFactoryInterface
{
    public function create(
        Category $category,
        ContactName $contactName,
        Email $email,
        Content $content,
        ?PrincipalIdentifier $principalIdentifier,
        Language $language,
    ): Contact;
}
