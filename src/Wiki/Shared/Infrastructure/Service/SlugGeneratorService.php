<?php

declare(strict_types=1);

namespace Source\Wiki\Shared\Infrastructure\Service;

use Source\Wiki\Shared\Domain\Service\SlugGeneratorServiceInterface;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\Slug;

readonly class SlugGeneratorService implements SlugGeneratorServiceInterface
{
    private const string ALPHANUMERIC = 'abcdefghijklmnopqrstuvwxyz0123456789';

    public function generate(string $text, ResourceType $resourceType): Slug
    {
        // 小文字に変換
        $slug = mb_strtolower($text);

        // アルファベット・数字以外（空白・記号など）をすべて - に変換
        $slug = preg_replace('/[^a-z0-9]+/u', '-', $slug);

        // 連続する - を1つに統合
        $slug = preg_replace('/-+/', '-', (string) $slug);

        // 先頭と末尾の - を削除
        $slug = trim((string) $slug, '-');

        $prefix = $resourceType->slugPrefix();
        $minBodyLength = Slug::MIN_LENGTH - mb_strlen($prefix) - 1;

        // 空またはprefix込み最小文字数未満の場合はランダムな10文字の英数字を生成
        if ($slug === '' || mb_strlen($slug) < $minBodyLength) {
            $slug = '';
            $max = strlen(self::ALPHANUMERIC) - 1;
            for ($i = 0; $i < 10; $i++) {
                $slug .= self::ALPHANUMERIC[random_int(0, $max)];
            }
        }

        $slug = $prefix . '-' . $slug;

        // 80文字を超える部分は切り詰め
        if (mb_strlen($slug) > Slug::MAX_LENGTH) {
            $slug = mb_substr($slug, 0, Slug::MAX_LENGTH);
            $slug = rtrim($slug, '-');
        }

        return new Slug($slug);
    }
}
