<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Application\UseCase\Query\ListVideos;

use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\Slug;

interface ListVideosInputPort
{
    public function minDurationSeconds(): ?int;

    public function maxDurationSeconds(): ?int;

    public function slug(): Slug;

    public function language(): Language;

    public function resourceType(): ResourceType;
}
