<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Domain\Repository;

use Source\SiteManagement\Contact\Domain\Entity\ReplyCotact;

interface ReplyContactRepositoryInterface
{
    public function save(ReplyCotact $replyCotact): void;
}
