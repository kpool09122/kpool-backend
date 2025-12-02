<?php

declare(strict_types=1);

namespace Source\Monetization\Payment\Domain\ValueObject;

enum PaymentStatus: string
{
    // 初期状態。まだゲートウェイ処理が行われていない
    case PENDING = 'pending';
    // オーソリ済み。与信が確保された状態（capture 可能）
    case AUTHORIZED = 'authorized';
    // 売上確定。返金のみ許可される
    case CAPTURED = 'captured';
    // 一部返金済み。追加返金で REFUNDED に移行し得る
    case PARTIALLY_REFUNDED = 'partially_refunded';
    // 全額返金済み。これ以上の状態遷移は想定しない
    case REFUNDED = 'refunded';
    // 与信/決済が失敗した状態（理由・時刻を保持）
    case FAILED = 'failed';
}
