<?php

declare(strict_types=1);

namespace Source\Wiki\Grading\Application\UseCase\Command\ProcessRolePromotion;

use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

class ProcessRolePromotionOutput implements ProcessRolePromotionOutputPort
{
    /** @var PrincipalIdentifier[] */
    private array $promoted = [];

    /** @var PrincipalIdentifier[] */
    private array $demoted = [];

    /** @var PrincipalIdentifier[] */
    private array $warned = [];

    /**
     * @param PrincipalIdentifier[] $promoted
     */
    public function setPromoted(array $promoted): void
    {
        $this->promoted = $promoted;
    }

    /**
     * @return PrincipalIdentifier[]
     */
    public function promoted(): array
    {
        return $this->promoted;
    }

    /**
     * @param PrincipalIdentifier[] $demoted
     */
    public function setDemoted(array $demoted): void
    {
        $this->demoted = $demoted;
    }

    /**
     * @return PrincipalIdentifier[]
     */
    public function demoted(): array
    {
        return $this->demoted;
    }

    /**
     * @param PrincipalIdentifier[] $warned
     */
    public function setWarned(array $warned): void
    {
        $this->warned = $warned;
    }

    /**
     * @return PrincipalIdentifier[]
     */
    public function warned(): array
    {
        return $this->warned;
    }
}
