# MonetizationAccount の決済/送金メタを別テーブルで管理する手順

## 目的
- カード/銀行口座の機微情報を保存せず、表示に必要な最小限メタ情報のみ保持する。
- メタ情報は JSON 1件ではなく別テーブルで複数件管理する。
- 例: 「Visa **** 4242」「MUFG **** 1234」を複数登録し、デフォルト選択できるようにする。

## 前提方針
- 保存しない: カード番号、CVC、口座番号のフル値。
- 保存する: Stripe の参照 ID と表示用メタ情報（brand/last4 など）。
- 情報ソースは Stripe（アプリはキャッシュ/表示用として保持）。

## データモデル（提案）
`monetization_accounts` に JSON を足すのではなく、下記2テーブルを追加する。

1. `monetization_payment_methods`（カード等）
- `id` (uuid, PK)
- `monetization_account_id` (uuid, FK)
- `stripe_payment_method_id` (string, unique)
- `type` (string) 例: `card`
- `brand` (string, nullable)
- `last4` (string, nullable)
- `exp_month` (unsignedTinyInteger, nullable)
- `exp_year` (unsignedSmallInteger, nullable)
- `is_default` (boolean, default false)
- `status` (string) 例: `active`, `inactive`
- `created_at`, `updated_at`

2. `monetization_payout_accounts`（送金口座）
- `id` (uuid, PK)
- `monetization_account_id` (uuid, FK)
- `stripe_external_account_id` (string, unique)
- `bank_name` (string, nullable)
- `last4` (string, nullable)
- `country` (string, nullable)
- `currency` (string, nullable)
- `account_holder_type` (string, nullable) 例: `individual`, `company`
- `is_default` (boolean, default false)
- `status` (string) 例: `active`, `inactive`
- `created_at`, `updated_at`

## 実装ステップ
1. Migration 追加
- 対象: `database/migrations`
- `monetization_payment_methods`, `monetization_payout_accounts` を新規作成。
- `monetization_account_id` に外部キー + index を追加。
- `stripe_payment_method_id`, `stripe_external_account_id` に unique 制約を追加。
- 可能なら「1アカウント1デフォルト」制約を DB かアプリロジックで担保する。

2. Eloquent Model 更新
- 対象:
  - `application/Models/Monetization/MonetizationPaymentMethod.php`（新規）
  - `application/Models/Monetization/MonetizationPayoutAccount.php`（新規）
  - `application/Models/Monetization/MonetizationAccount.php`（relation追加）
- `MonetizationAccount` には `hasMany` を定義。
- 新規2モデルに `fillable` / `casts` / `belongsTo` を定義。

3. Domain ValueObject 追加
- 対象:
  - `src/Monetization/Account/Domain/ValueObject`
  - `src/Monetization/Account/Domain/Entity`
- 追加例:
  - `PaymentMethodMeta`（card brand/last4/exp など）
  - `PayoutAccountMeta`（bank_name/last4/account_holder_type など）
  - `AccountHolderType` enum
- 必要なら `PaymentMethodStatus`, `PayoutAccountStatus` enum も追加。

4. Entity 更新
- 対象: `src/Monetization/Account/Domain/Entity/MonetizationAccount.php`
- `MonetizationAccount` に単一メタを持たせない。
- 代わりに下記を導入:
  - `PaymentMethod` エンティティ（新規）
  - `PayoutAccount` エンティティ（新規）
- 集約境界は2案:
  - A: Account集約の子として扱う
  - B: 独立集約として Repository 分離（推奨、実装が単純）

5. Factory 更新
- 対象: `src/Monetization/Account/Infrastructure/Factory/MonetizationAccountFactory.php`
- `MonetizationAccount` 生成は現状維持（メタを直接持たないため変更最小）。
- 追加で `PaymentMethodFactory`, `PayoutAccountFactory` を作成してもよい。

6. Repository 更新
- 新規 Repository を追加:
  - `PaymentMethodRepositoryInterface` / 実装
  - `PayoutAccountRepositoryInterface` / 実装
- `MonetizationAccountRepository` は基本そのまま。
- 取得系に以下を用意:
  - `findByStripePaymentMethodId(...)`
  - `findByStripeExternalAccountId(...)`
  - `findDefaultByMonetizationAccountId(...)`

7. 更新ユースケース追加
- 新規ユースケースを `src/Monetization/Account/Application/UseCase/Command/...` に追加。
- 例:
  - `UpsertPaymentMethodMeta`
  - `UpsertPayoutAccountMeta`
  - `SetDefaultPaymentMethod`
  - `SetDefaultPayoutAccount`
- 既存なし -> 作成時、更新時ともに upsert で扱う。

8. カードメタ同期（決済イベント起点）
- 起点: 決済ドメインの既存イベント（例: authorize/capture 成功イベント）。
- フロー:
  - イベント受信 -> MonetizationAccount を取得
  - `stripe_payment_method_id` で既存検索
  - 未登録なら insert、既存なら差分更新
- 実装:
  - イベントハンドラを追加し、`UpsertPaymentMethodMeta` を呼ぶ。
  - イベントは重複配送され得るため、同一内容の再処理に耐える実装にする（冪等）。

9. 銀行口座メタ同期（Webhook 起点）
- 起点: Stripe Connect の external account 系 Webhook。
- フロー:
  - Webhook 検証 -> 対象 Connected Account を特定
  - `stripe_external_account_id` で既存検索
  - 未登録なら insert、既存なら差分更新
  - `UpsertPayoutAccountMeta` を呼ぶ
- 実装:
  - 署名検証とイベント ID ベース冪等化を必須にする。
  - Stripe を正、アプリを表示用キャッシュとして扱う。

10. テスト追加
- Unit:
  - ValueObject/Entity バリデーション
  - default 切替時の整合性（他を false にできるか）
  - Repository の save/load round-trip
- UseCase:
  - 正常更新、対象なし、部分更新（cardのみ/bankのみ）
  - 同一 Stripe ID の重複登録防止（unique制約）
- Integration:
  - migration 後の insert/select
  - Webhook 処理がある場合は payload から保存まで

## 同期方針（今回の決定）
- カードメタ:
  - 決済ドメインのイベントで同期する。
  - `stripe_payment_method_id` をキーに upsert する。
- 銀行口座メタ:
  - Webhook で銀行口座登録ユースケース（または upsert ユースケース）を呼ぶ。
  - `stripe_external_account_id` をキーに upsert する。
- 共通:
  - 冪等化（重複イベント/Webhook対策）を必須とする。

## 変更対象ファイル一覧（想定）
- `database/migrations/*_create_monetization_payment_methods_table.php`
- `database/migrations/*_create_monetization_payout_accounts_table.php`
- `application/Models/Monetization/MonetizationAccount.php`
- `application/Models/Monetization/MonetizationPaymentMethod.php`
- `application/Models/Monetization/MonetizationPayoutAccount.php`
- `src/Monetization/Account/Domain/Entity/PaymentMethod.php`
- `src/Monetization/Account/Domain/Entity/PayoutAccount.php`
- `src/Monetization/Account/Domain/ValueObject/PaymentMethodMeta.php`
- `src/Monetization/Account/Domain/ValueObject/PayoutAccountMeta.php`
- `src/Monetization/Account/Domain/ValueObject/AccountHolderType.php`
- `src/Monetization/Account/Domain/Repository/PaymentMethodRepositoryInterface.php`
- `src/Monetization/Account/Domain/Repository/PayoutAccountRepositoryInterface.php`
- `src/Monetization/Account/Infrastructure/Repository/PaymentMethodRepository.php`
- `src/Monetization/Account/Infrastructure/Repository/PayoutAccountRepository.php`
- `src/Monetization/Account/Application/UseCase/Command/UpsertPaymentMethodMeta/*`
- `src/Monetization/Account/Application/UseCase/Command/UpsertPayoutAccountMeta/*`
- `src/Monetization/Account/Application/UseCase/Command/SetDefaultPaymentMethod/*`
- `src/Monetization/Account/Application/UseCase/Command/SetDefaultPayoutAccount/*`
- `tests/Monetization/Account/...`
