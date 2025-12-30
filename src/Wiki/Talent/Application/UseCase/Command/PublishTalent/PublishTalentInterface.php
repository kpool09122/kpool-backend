<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Application\UseCase\Command\PublishTalent;

use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Talent\Application\Exception\ExistsApprovedButNotTranslatedTalentException;
use Source\Wiki\Talent\Application\Exception\TalentNotFoundException;
use Source\Wiki\Talent\Domain\Entity\Talent;
use Source\Wiki\Talent\Domain\Exception\ExceedMaxRelevantVideoLinksException;

interface PublishTalentInterface
{
    /**
     * @param PublishTalentInputPort $input
     * @return Talent
     * @throws TalentNotFoundException
     * @throws InvalidStatusException
     * @throws ExistsApprovedButNotTranslatedTalentException
     * @throws ExceedMaxRelevantVideoLinksException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function process(PublishTalentInputPort $input): Talent;
}
