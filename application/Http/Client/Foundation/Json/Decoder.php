<?php

declare(strict_types=1);

namespace Application\Http\Client\Foundation\Json;

use JsonException;

final class Decoder
{
    /**
     * @throws JsonException
     */
    public static function decode(string $json, bool $assoc = false): mixed
    {
        return json_decode($json, $assoc, 512, JSON_THROW_ON_ERROR);
    }
}
