<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Query\ListPasskeys;

use Source\Identity\Application\UseCase\Query\PasskeyReadModel;

interface ListPasskeysInterface
{
    /** @return PasskeyReadModel[] */
    public function process(ListPasskeysInputPort $input): array;
}
