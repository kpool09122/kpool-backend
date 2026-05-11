<?php

declare(strict_types=1);

namespace Application\Http\Action\Identity\Support;

use Source\Shared\Infrastructure\Support\ImageUrl;

final readonly class IdentityResponsePayload
{
    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public static function normalizeProfileImage(array $payload): array
    {
        if (! array_key_exists('profileImage', $payload)) {
            return $payload;
        }

        $profileImage = $payload['profileImage'];
        $payload['profileImage'] = is_string($profileImage) ? ImageUrl::fromPath($profileImage) : null;

        return $payload;
    }
}
