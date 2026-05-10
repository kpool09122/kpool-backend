<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Infrastructure\Query;

use Application\Models\Wiki\Principal as PrincipalModel;
use Source\Wiki\Principal\Application\UseCase\Query\GetCurrentPrincipal\GetCurrentPrincipalInputPort;
use Source\Wiki\Principal\Application\UseCase\Query\GetCurrentPrincipal\GetCurrentPrincipalInterface;
use Source\Wiki\Principal\Application\UseCase\Query\PrincipalReadModel;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;

readonly class GetCurrentPrincipal implements GetCurrentPrincipalInterface
{
    /**
     * @throws PrincipalNotFoundException
     */
    public function process(GetCurrentPrincipalInputPort $input): PrincipalReadModel
    {
        $principal = PrincipalModel::query()
            ->where('identity_id', (string) $input->identityIdentifier())
            ->first();

        if ($principal === null) {
            throw new PrincipalNotFoundException();
        }

        return $this->toReadModel($principal);
    }

    private function toReadModel(PrincipalModel $principal): PrincipalReadModel
    {
        return new PrincipalReadModel(
            principalIdentifier: $principal->id,
            identityIdentifier: $principal->identity_id,
            isDelegatedPrincipal: $principal->delegation_identifier !== null,
            isEnabled: $principal->enabled,
        );
    }
}
