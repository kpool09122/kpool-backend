<?php

declare(strict_types=1);

namespace Tests\Helper;

use PHPUnit\Framework\Assert;

final class JsonFixture
{
    /** @return list<string> */
    public static function identifiers(mixed $json): array
    {
        Assert::assertIsString($json);
        $identifiers = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        Assert::assertIsArray($identifiers);
        Assert::assertTrue(array_is_list($identifiers));
        $result = [];
        foreach ($identifiers as $identifier) {
            Assert::assertIsString($identifier);
            $result[] = $identifier;
        }

        return $result;
    }
}
