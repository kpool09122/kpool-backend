<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Application\UseCase\Command\TranslateTalent;

use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Talent\Application\Exception\TalentNotFoundException;
use Source\Wiki\Talent\Domain\Entity\DraftTalent;

interface TranslateTalentInterface
{
    /**
     * @param TranslateTalentInputPort $input
     * @return DraftTalent[]
     * @throws TalentNotFoundException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function process(TranslateTalentInputPort $input): array;
}
