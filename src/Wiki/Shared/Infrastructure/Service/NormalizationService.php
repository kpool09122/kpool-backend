<?php

declare(strict_types=1);

namespace Source\Wiki\Shared\Infrastructure\Service;

use RuntimeException;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Shared\Domain\Service\NormalizationServiceInterface;

// TODO: mecabがPHP8.4に対応したら、コマンドライン経由の実装を切り替える
class NormalizationService implements NormalizationServiceInterface
{
    private const array KOREAN_CHOSEONG = [
        'ㄱ', 'ㄲ', 'ㄴ', 'ㄷ', 'ㄸ', 'ㄹ', 'ㅁ', 'ㅂ', 'ㅃ',
        'ㅅ', 'ㅆ', 'ㅇ', 'ㅈ', 'ㅉ', 'ㅊ', 'ㅋ', 'ㅌ', 'ㅍ', 'ㅎ',
    ];

    private const int HANGUL_START = 0xAC00;
    private const int HANGUL_END = 0xD7A3;
    private const int CHOSEONG_COUNT = 588;

    public function __construct(
        private readonly string $mecabPath = 'mecab',
    ) {
    }

    public function normalize(string $value, Language $language): string
    {
        return match ($language) {
            Language::JAPANESE => $this->normalizeJapanese($value),
            Language::KOREAN => $this->normalizeKorean($value),
            Language::ENGLISH => $this->normalizeEnglish($value),
        };
    }

    private function normalizeJapanese(string $value): string
    {
        // 1. MeCabで読みを取得（-Oyomi オプションでカタカナ読みを出力）
        $reading = $this->execMecab($value);

        // 2. カタカナ→ひらがな変換
        $result = mb_convert_kana($reading, 'c', 'UTF-8');

        // 3. アルファベットを小文字に変換
        return mb_strtolower($result, 'UTF-8');
    }

    private function execMecab(string $value): string
    {
        $process = proc_open(
            [$this->mecabPath, '-Oyomi'],
            [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes
        );

        if ($process === false) {
            throw new RuntimeException('Failed to start MeCab process');
        }

        fwrite($pipes[0], $value);
        fclose($pipes[0]);

        $output = stream_get_contents($pipes[1]);
        fclose($pipes[1]);

        $error = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        if ($exitCode !== 0) {
            throw new RuntimeException('MeCab process failed: ' . $error);
        }

        return trim($output);
    }

    private function normalizeKorean(string $value): string
    {
        $result = '';
        $length = mb_strlen($value, 'UTF-8');

        for ($i = 0; $i < $length; $i++) {
            $char = mb_substr($value, $i, 1, 'UTF-8');
            $code = mb_ord($char, 'UTF-8');

            if ($code >= self::HANGUL_START && $code <= self::HANGUL_END) {
                $choseongIndex = (int) (($code - self::HANGUL_START) / self::CHOSEONG_COUNT);
                $result .= self::KOREAN_CHOSEONG[$choseongIndex];
            } else {
                $result .= $char;
            }
        }

        // アルファベットを小文字に変換
        return mb_strtolower($result, 'UTF-8');
    }

    private function normalizeEnglish(string $value): string
    {
        return mb_strtolower($value, 'UTF-8');
    }
}
