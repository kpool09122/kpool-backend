<?php

declare(strict_types=1);

if (! function_exists('error_message')) {
    function error_message(string $key, string $language): string
    {
        return __("errors.{$key}", [], $language);
    }
}
