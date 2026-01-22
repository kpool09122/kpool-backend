<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\ProcessRolePromotion;

use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

interface ProcessRolePromotionOutputPort
{
    /**
     * @param PrincipalIdentifier[] $promoted
     */
    public function setPromoted(array $promoted): void;

    /**
     * @return PrincipalIdentifier[]
     */
    public function promoted(): array;

    /**
     * @param PrincipalIdentifier[] $demoted
     */
    public function setDemoted(array $demoted): void;

    /**
     * @return PrincipalIdentifier[]
     */
    public function demoted(): array;

    /**
     * @param PrincipalIdentifier[] $warned
     */
    public function setWarned(array $warned): void;

    /**
     * @return PrincipalIdentifier[]
     */
    public function warned(): array;
}
