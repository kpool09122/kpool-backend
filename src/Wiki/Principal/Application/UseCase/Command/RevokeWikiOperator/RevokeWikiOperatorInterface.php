<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\RevokeWikiOperator;

interface RevokeWikiOperatorInterface
{
    public function process(RevokeWikiOperatorInputPort $input, RevokeWikiOperatorOutputPort $output): void;
}
