<?php

declare(strict_types=1);

namespace Source\Account\AccountVerification\Domain\ValueObject;

enum DocumentType: string
{
    // 個人（Talent）向け
    case RESIDENT_REGISTRATION = 'resident_registration';  // 韓国: 住民登録証
    case PASSPORT = 'passport';                            // パスポート
    case DRIVER_LICENSE = 'driver_license';                // 運転免許証
    case SELFIE = 'selfie';                                // 顔写真（セルフィー）

    // 法人（Agency）向け
    case BUSINESS_REGISTRATION = 'business_registration';  // 韓国: 事業者登録証
    case CORPORATE_REGISTRY = 'corporate_registry';        // 日本: 登記簿謄本
    case INCORPORATION_DOCUMENT = 'incorporation_document'; // その他: 法人登記書類
    case REPRESENTATIVE_ID = 'representative_id';          // 代表者身分証
}
