<?php

declare(strict_types=1);

namespace Source\Monetization\Billing\Domain\ValueObject;

enum TaxDocumentType: string
{
    // 日本の適格請求書（インボイス）
    case JP_QUALIFIED_INVOICE = 'jp_qualified_invoice';
    // 韓国の電子税計算書
    case KR_ELECTRONIC_TAX_INVOICE = 'kr_electronic_tax_invoice';
    // 逆課税通知（売り手未登録時のB2Bなど）
    case REVERSE_CHARGE_NOTICE = 'reverse_charge_notice';
    // カード決済のレシート相当
    case CARD_RECEIPT = 'card_receipt';
    // 現金領収書相当
    case CASH_RECEIPT = 'cash_receipt';
    // 簡易領収書（上記以外の汎用）
    case SIMPLE_RECEIPT = 'simple_receipt';
}
