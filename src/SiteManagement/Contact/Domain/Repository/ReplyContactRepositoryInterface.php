<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Domain\Repository;

use Source\SiteManagement\Contact\Domain\Entity\Reply;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactIdentifier;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactReplyIdentifier;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;

interface ReplyContactRepositoryInterface
{
    public function save(Reply $reply): void;

    public function findById(ContactReplyIdentifier $contactReplyIdentifier): ?Reply;

    public function findLatestFromSubmitter(ContactIdentifier $contactIdentifier, PrincipalIdentifier $principalIdentifier): ?Reply;

    public function findLatestFromOperator(ContactIdentifier $contactIdentifier, PrincipalIdentifier $principalIdentifier): ?Reply;
}
