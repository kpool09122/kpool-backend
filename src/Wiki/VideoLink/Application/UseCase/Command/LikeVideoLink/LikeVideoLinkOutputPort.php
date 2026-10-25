<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Application\UseCase\Command\LikeVideoLink;

interface LikeVideoLinkOutputPort
{
    /** @return array{} */
    public function toArray(): array;
}
