<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query\SearchMasterWikis;

use InvalidArgumentException;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

readonly class SearchMasterWikisInput implements SearchMasterWikisInputPort
{
    private const SUPPORTED_RESOURCE_TYPES = [
        ResourceType::AGENCY,
        ResourceType::GROUP,
        ResourceType::TALENT,
        ResourceType::SONG,
    ];

    public function __construct(
        private Language $language,
        private ResourceType $resourceType,
        private string $keyword,
        private ?int $limit = null,
    ) {
        if (! in_array($this->resourceType, self::SUPPORTED_RESOURCE_TYPES, true)) {
            throw new InvalidArgumentException('resourceType is not supported.');
        }

        if (trim($this->keyword) === '') {
            throw new InvalidArgumentException('keyword is required.');
        }

        if ($this->limit !== null && ($this->limit < 1 || $this->limit > 50)) {
            throw new InvalidArgumentException('limit must be between 1 and 50.');
        }
    }

    public function language(): Language
    {
        return $this->language;
    }

    public function resourceType(): ResourceType
    {
        return $this->resourceType;
    }

    public function keyword(): string
    {
        return trim($this->keyword);
    }

    public function limit(): int
    {
        return $this->limit ?? 10;
    }
}
