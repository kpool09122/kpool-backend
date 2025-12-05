<?php

declare(strict_types=1);

namespace Source\Monetization\Billing\Domain\ValueObject;

enum InvoiceStatus: string
{
    // 発行済み。支払い/キャンセルの対象となる初期状態
    case ISSUED = 'issued';
    // 支払い完了。これ以上の請求操作は不要
    case PAID = 'paid';
    // 失効/取消。支払前に無効化された状態
    case VOID = 'void';
}
