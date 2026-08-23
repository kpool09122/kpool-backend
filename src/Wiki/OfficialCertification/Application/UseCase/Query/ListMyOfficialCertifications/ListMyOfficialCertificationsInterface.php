<?php

declare(strict_types=1);

namespace Source\Wiki\OfficialCertification\Application\UseCase\Query\ListMyOfficialCertifications;

use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;

interface ListMyOfficialCertificationsInterface
{
    /**
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    public function process(ListMyOfficialCertificationsInputPort $input, ListMyOfficialCertificationsOutputPort $output): void;
}
