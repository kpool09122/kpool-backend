<?php

declare(strict_types=1);

namespace Source\Wiki\Shared\Domain\ValueObject;

use InvalidArgumentException;
use Source\Shared\Application\Service\Uuid\UuidValidator;

final readonly class Resource
{
    /**
     * @param ResourceType $type
     * @param string|null $agencyId
     * @param string[] $groupIds
     * @param string[] $talentIds
     * @param bool $isOfficial
     * @param string|null $editorId
     */
    public function __construct(
        private ResourceType $type,
        private ?string $agencyId = null,
        private array $groupIds = [],
        private array $talentIds = [],
        private bool $isOfficial = false,
        private ?string $editorId = null,
    ) {
        $this->validate($agencyId, $this->groupIds, $this->talentIds, $editorId);
    }

    public function type(): ResourceType
    {
        return $this->type;
    }

    public function agencyId(): ?string
    {
        return $this->agencyId;
    }

    /**
     * @return string[]
     */
    public function groupIds(): array
    {
        return $this->groupIds;
    }

    /**
     * @return string[]
     */
    public function talentIds(): array
    {
        return $this->talentIds;
    }

    public function isOfficial(): bool
    {
        return $this->isOfficial;
    }

    public function editorId(): ?string
    {
        return $this->editorId;
    }

    /**
     * @param string|null $agencyId
     * @param string[] $groupIds
     * @param string[] $talentIds
     * @param string|null $editorId
     * @return void
     */
    public function validate(
        ?string $agencyId,
        array $groupIds,
        array $talentIds,
        ?string $editorId,
    ): void {
        if ($agencyId !== null && ! UuidValidator::isValid($agencyId)) {
            throw new InvalidArgumentException('Agency id is invalid.');
        }

        if ($editorId !== null && ! UuidValidator::isValid($editorId)) {
            throw new InvalidArgumentException('Editor id is invalid.');
        }

        foreach ($groupIds as $gid) {
            if (! is_string($gid) || ! UuidValidator::isValid($gid)) {
                throw new InvalidArgumentException('Group ids contain invalid value.');
            }
        }

        foreach ($talentIds as $tid) {
            if (! is_string($tid) || ! UuidValidator::isValid($tid)) {
                throw new InvalidArgumentException('Talent ids contain invalid value.');
            }
        }
    }
}
