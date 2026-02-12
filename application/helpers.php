<?php

declare(strict_types=1);

if (! function_exists('error_message')) {
    function error_message(string $key, string $language): string
    {
        return __("errors.{$key}", [], $language);
    }
}

if (! function_exists('section_title')) {
    function section_title(string $key, string $language): string
    {
        return __("section_titles.{$key}", [], $language);
    }
}
