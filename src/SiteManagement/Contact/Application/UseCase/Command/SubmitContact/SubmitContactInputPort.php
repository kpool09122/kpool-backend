<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Application\UseCase\Command\SubmitContact;

use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\Language;
use Source\SiteManagement\Contact\Domain\ValueObject\Category;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactName;
use Source\SiteManagement\Contact\Domain\ValueObject\Content;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;

interface SubmitContactInputPort
{
    public function principalIdentifier(): ?PrincipalIdentifier;

    public function category(): Category;

    public function name(): ContactName;

    public function email(): Email;

    public function content(): Content;

    public function language(): Language;
}
