<?php

declare(strict_types=1);

namespace Source\Identity\Infrastructure\Query;

use Application\Models\Identity\Identity as IdentityModel;
use Source\Identity\Application\UseCase\Query\GetIdentityProfile\GetIdentityProfileInputPort;
use Source\Identity\Application\UseCase\Query\GetIdentityProfile\GetIdentityProfileInterface;
use Source\Identity\Application\UseCase\Query\IdentityProfileReadModel;
use Source\Identity\Domain\Exception\IdentityNotFoundException;
use Source\Shared\Infrastructure\Support\ImageUrl;

readonly class GetIdentityProfile implements GetIdentityProfileInterface
{
    /**
     * @throws IdentityNotFoundException
     */
    public function process(GetIdentityProfileInputPort $input): IdentityProfileReadModel
    {
        $model = IdentityModel::query()
            ->where('id', (string) $input->identityIdentifier())
            ->first();

        if ($model === null) {
            throw new IdentityNotFoundException();
        }

        return new IdentityProfileReadModel(
            identityIdentifier: $model->id,
            identityName: $model->identity_name,
            language: $model->language,
            profileImage: ImageUrl::fromPath($model->profile_image),
        );
    }
}
