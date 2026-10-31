<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Domain\Repository;

use Source\SiteManagement\Contact\Domain\Entity\ReplyContact as ReplyContactEntity;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactReplyIdentifier;

interface ReplyContactRepositoryInterface
{
    public function save(ReplyContactEntity $replyContact): void;

    public function findById(ContactReplyIdentifier $contactReplyIdentifier): ?ReplyContactEntity;
}
