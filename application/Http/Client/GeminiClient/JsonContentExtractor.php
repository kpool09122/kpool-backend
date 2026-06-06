<?php

declare(strict_types=1);

namespace Application\Http\Client\GeminiClient;

use Application\Http\Client\Foundation\Json\Decoder;

final class JsonContentExtractor
{
    /**
     * @return array<string, mixed>
     */
    public static function decodeObject(string $content): array
    {
        $json = trim($content);

        if (preg_match('/^```(?:json)?\s*(.*?)\s*```$/s', $json, $matches) === 1) {
            $json = trim($matches[1]);
        } else {
            $start = strpos($json, '{');
            $end = strrpos($json, '}');
            if ($start !== false && $end !== false && $end >= $start) {
                $json = substr($json, $start, $end - $start + 1);
            }
        }

        $data = Decoder::decode($json, true);

        return is_array($data) ? $data : [];
    }
}
