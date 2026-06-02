<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Command\WithdrawWiki;

use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;

interface WithdrawWikiInterface
{
    /**
     * @throws WikiNotFoundException
     * @throws InvalidStatusException
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    public function process(WithdrawWikiInputPort $input, WithdrawWikiOutputPort $output): void;
}
