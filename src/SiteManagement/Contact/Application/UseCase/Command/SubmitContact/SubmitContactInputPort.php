<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Application\UseCase\Command\SubmitContact;

use Source\Shared\Domain\ValueObject\Email;
use Source\SiteManagement\Contact\Domain\ValueObject\Category;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactName;
use Source\SiteManagement\Contact\Domain\ValueObject\Content;

interface SubmitContactInputPort
{
    public function category(): Category;

    public function name(): ContactName;

    public function email(): Email;

    public function content(): Content;
}
