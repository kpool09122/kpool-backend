<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\GrantWikiOperator;

interface GrantWikiOperatorInterface
{
    public function process(GrantWikiOperatorInputPort $input, GrantWikiOperatorOutputPort $output): void;
}
