<?php

declare(strict_types=1);

namespace Source\Account\AccountVerification\Domain\ValueObject;

enum RejectionReasonCode: string
{
    case DOCUMENT_UNCLEAR = 'document_unclear';       // 書類が不鮮明
    case DOCUMENT_EXPIRED = 'document_expired';       // 書類の有効期限切れ
    case DOCUMENT_MISMATCH = 'document_mismatch';     // 名前/情報の不一致
    case DOCUMENT_INCOMPLETE = 'document_incomplete'; // 必要書類の不足
    case FRAUDULENT_DOCUMENT = 'fraudulent_document'; // 偽造の疑い
    case OTHER = 'other';                             // その他

    public function requiresDetail(): bool
    {
        return $this === self::OTHER;
    }
}
