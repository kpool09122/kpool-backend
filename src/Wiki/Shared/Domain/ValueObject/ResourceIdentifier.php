<?php

declare(strict_types=1);

namespace Source\Wiki\Shared\Domain\ValueObject;

use InvalidArgumentException;
use Source\Shared\Application\Service\Uuid\UuidValidator;

final readonly class ResourceIdentifier
{
    /**
     * @param ResourceType $type
     * @param string|null $agencyId
     * @param string[] $groupIds
     * @param string[] $talentIds
     */
    public function __construct(
        private ResourceType $type,
        private ?string $agencyId = null,
        private array $groupIds = [],
        private array $talentIds = [],
    ) {
        $this->validate($agencyId, $this->groupIds, $this->talentIds);
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

    /**
     * @param string|null $agencyId
     * @param string[] $groupIds
     * @param string[] $talentIds
     * @return void
     */
    public function validate(
        ?string $agencyId,
        array $groupIds,
        array $talentIds,
    ): void {
        if ($agencyId !== null && ! UuidValidator::isValid($agencyId)) {
            throw new InvalidArgumentException('Agency id is invalid.');
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
