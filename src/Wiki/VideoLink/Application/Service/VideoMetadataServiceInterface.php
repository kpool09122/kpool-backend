<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Application\Service;

use InvalidArgumentException;
use Source\Wiki\VideoLink\Application\Exception\VideoMetadataUnavailableException;

interface VideoMetadataServiceInterface
{
    /**
     * @throws InvalidArgumentException
     * @throws VideoMetadataUnavailableException
     */
    public function resolve(string $url): VideoMetadata;
}
