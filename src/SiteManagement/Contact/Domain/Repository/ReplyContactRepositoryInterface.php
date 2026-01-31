<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Domain\Repository;

use Source\SiteManagement\Contact\Domain\Entity\ReplyCotact;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactReplyIdentifier;

interface ReplyContactRepositoryInterface
{
    public function save(ReplyCotact $replyCotact): void;

    public function findById(ContactReplyIdentifier $contactReplyIdentifier): ?ReplyCotact;
}
