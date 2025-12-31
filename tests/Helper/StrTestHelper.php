<?php

declare(strict_types=1);

namespace Tests\Helper;

use Exception;
use RuntimeException;
use Symfony\Component\Uid\Uuid;

class StrTestHelper
{
    private const int DEFAULT_LENGTH = 10;
    private const int MIN_DOMAIN_LABEL_LENGTH = 1;

    private const int MAX_DOMAIN_LABEL_LENGTH = 63;

    /**
     * @param int $length
     * @param string[] $baseChars
     * @return string
     */
    public static function generateStr(int $length = self::DEFAULT_LENGTH, array $baseChars = []): string
    {
        try {
            $res = '';
            $baseChars = count($baseChars) > 0
                ? $baseChars
                : ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 0, 1, 2, 3, 4, 5, 6, 7, 8, 9];

            for ($i = 0; $i < $length; $i++) {
                $res .= $baseChars[random_int(0, count($baseChars) - 1)];
            }
        } catch (Exception $e) {
            throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
        }

        return $res;
    }

    /**
     * 渡された値に応じて、小文字アルファベットのみのランダムな文字列を生成します。
     *
     * @param int $length
     * @return string
     */
    public static function generateSmallAlphaStr(int $length = self::DEFAULT_LENGTH): string
    {
        return self::generateStr($length, ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z']);
    }

    /**
     * 渡された値に応じて、数字のみのランダムな文字列を生成します。
     *
     * @param int $length
     * @return string
     */
    public static function generateNumberStr(int $length = self::DEFAULT_LENGTH): string
    {
        return self::generateStr($length, ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9']);
    }

    public static function generateHttp(bool $ssl = false, int $length = 2083): string
    {
        try {
            $scheme = $ssl ? 'http://' : 'https://';
            $topDomain = '.test';
            $secondDomainLength = self::generateStr(
                random_int(
                    self::MIN_DOMAIN_LABEL_LENGTH,
                    self::MAX_DOMAIN_LABEL_LENGTH
                )
            );
            $url = $scheme . $secondDomainLength . $topDomain . DIRECTORY_SEPARATOR;
            $url .= self::generateStr($length - mb_strlen($url));
        } catch (Exception $e) {
            throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
        }

        return $url;
    }

    public static function generateHex(int $length = self::DEFAULT_LENGTH): string
    {
        try {
            return bin2hex(random_bytes((int)($length / 2)));
        } catch (Exception $e) {
            exit($e->getMessage());
        }
    }

    public static function generateUuid(): string
    {
        return (string) Uuid::v7();
    }
}
