<?php

declare(strict_types=1);

namespace Source\Wiki\Shared\Domain\ValueObject;

use InvalidArgumentException;
use Source\Shared\Application\Service\Ulid\UlidValidator;

final readonly class ResourceIdentifier
{
    /**
     * @param ResourceType $type
     * @param string|null $agencyId
     * @param string[] $groupIds
     * @param string|null $talentId
     */
    public function __construct(
        private ResourceType $type,
        private ?string $agencyId = null,
        private array $groupIds = [],
        private ?string $talentId = null,
    ) {
        $this->validate($agencyId, $this->groupIds, $talentId);
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

    public function talentId(): ?string
    {
        return $this->talentId;
    }

    /**
     * @param string|null $agencyId
     * @param string[] $groupIds
     * @param string|null $talentId
     * @return void
     */
    public function validate(
        ?string $agencyId,
        array $groupIds,
        ?string $talentId,
    ): void {
        if ($agencyId !== null && ! UlidValidator::isValid($agencyId)) {
            throw new InvalidArgumentException('Agency id is invalid.');
        }

        foreach ($groupIds as $gid) {
            if (! is_string($gid) || ! UlidValidator::isValid($gid)) {
                throw new InvalidArgumentException('Group ids contain invalid value.');
            }
        }

        if ($talentId !== null && ! UlidValidator::isValid($talentId)) {
            throw new InvalidArgumentException('Talent id is invalid.');
        }
    }
}
