<?php

declare(strict_types=1);

namespace Source\Account\Account\Domain\ValueObject;

enum DeletionBlockReason: string
{
    // Billing
    case UNPAID_INVOICES = 'unpaid_invoices'; // 未払い請求書が残っている
    case ACTIVE_SUBSCRIPTION = 'active_subscription'; // 定期課金が解約/キャンセルされていない
    case REFUND_POLICY_UNDECIDED = 'refund_policy_undecided'; // 返金・違約金の扱いが未確定
    case EXTERNAL_BILLING_DEPENDENCIES = 'external_billing_dependencies'; // Stripe Connect 等の外部課金連携が残っている

    // Ownership / Admin
    case OWNERSHIP_UNCONFIRMED = 'ownership_unconfirmed'; // オーナー確認/移譲が未完了
    case PRIVILEGED_ASSETS_NOT_TRANSFERRED = 'privileged_assets_not_transferred'; // ドメイン・APIキー等の権限資産が未移譲/未無効化

    // Legal / Compliance
    case LEGAL_HOLD = 'legal_hold'; // 法的ホールド・訴訟対応中
    case RETENTION_REQUIREMENT = 'retention_requirement'; // 保存義務期間内で削除不可
    case AUDIT_IN_PROGRESS = 'audit_in_progress'; // 監査対応中
    case DATA_EXPORT_PENDING = 'data_export_pending'; // GDPR/CCPA 等のデータエクスポート要求が未完了

    // Data / Integrations
    case EXTERNAL_INTEGRATIONS_ACTIVE = 'external_integrations_active'; // Webhook/API/SCIM 等の外部連携が有効
    case SHARED_DEPENDENCIES = 'shared_dependencies'; // 他リソース・他アカウントとの共有依存が残っている
    case BACKUP_POLICY_UNDECIDED = 'backup_policy_undecided'; // バックアップ/アーカイブ方針が未確定

    // Operational
    case OPEN_TICKETS = 'open_tickets'; // サポート/チケットが未解決
    case SCHEDULED_JOBS_ACTIVE = 'scheduled_jobs_active'; // 定期ジョブやバッチが稼働中

    // Security
    case SECURITY_CREDENTIALS_ACTIVE = 'security_credentials_active'; // APIキー/OAuthクライアントが有効のまま
    case SSO_STILL_ACTIVE = 'sso_still_active'; // IdP/SSO連携が有効のまま
}
