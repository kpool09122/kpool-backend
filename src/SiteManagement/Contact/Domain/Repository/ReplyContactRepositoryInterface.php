<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Domain\Repository;

use Source\SiteManagement\Contact\Domain\Entity\ReplyCotact;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactIdentifier;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactReplyIdentifier;

interface ReplyContactRepositoryInterface
{
    public function hasSuccessfulOperatorReply(ContactIdentifier $contactIdentifier): bool;

    public function save(ReplyCotact $replyCotact): void;

    public function findById(ContactReplyIdentifier $contactReplyIdentifier): ?ReplyCotact;
}
