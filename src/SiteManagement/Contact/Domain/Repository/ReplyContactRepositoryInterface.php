<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Domain\Repository;

use Source\SiteManagement\Contact\Domain\Entity\Reply;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactReplyIdentifier;

interface ReplyContactRepositoryInterface
{
    public function save(Reply $reply): void;

    public function findById(ContactReplyIdentifier $contactReplyIdentifier): ?Reply;
}
