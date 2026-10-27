<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Infrastructure\Service;

use Source\Wiki\VideoLink\Application\Exception\VideoMetadataUnavailableException;

interface PublicVideoHostResolverInterface
{
    /** @throws VideoMetadataUnavailableException */
    public function resolve(string $host): string;
}
