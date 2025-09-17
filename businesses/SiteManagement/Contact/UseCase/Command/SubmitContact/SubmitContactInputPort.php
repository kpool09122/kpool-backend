<?php

declare(strict_types=1);

namespace Businesses\SiteManagement\Contact\UseCase\Command\SubmitContact;

use Businesses\Shared\ValueObject\Email;
use Businesses\SiteManagement\Contact\Domain\ValueObject\Category;
use Businesses\SiteManagement\Contact\Domain\ValueObject\ContactName;
use Businesses\SiteManagement\Contact\Domain\ValueObject\Content;

interface SubmitContactInputPort
{
    public function category(): Category;

    public function name(): ContactName;

    public function email(): Email;

    public function content(): Content;
}
