<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Application\UseCase\Query\GetContactDetail;

use Source\SiteManagement\Contact\Application\UseCase\Query\ContactDetailReadModel;

class GetContactDetailOutput implements GetContactDetailOutputPort
{
    private ?ContactDetailReadModel $contact = null;

    public function output(ContactDetailReadModel $contact): void
    {
        $this->contact = $contact;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return $this->contact?->toArray() ?? [];
    }
}
