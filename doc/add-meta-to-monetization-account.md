# MonetizationAccount に表示用メタ情報を追加する手順

## 目的
- カード/銀行口座の機微情報を保存せず、表示に必要な最小限メタ情報のみ保持する。
- 例: 「Visa **** 4242」「MUFG **** 1234」を表示できるようにする。

## 前提方針
- 保存しない: カード番号、CVC、口座番号のフル値。
- 保存する: Stripe の参照 ID と表示用メタ情報（brand/last4 など）。
- 情報ソースは Stripe（アプリはキャッシュ/表示用として保持）。

## 追加するデータ項目（提案）
`monetization_accounts` に JSON カラムを2つ追加する。

- `card_meta` (nullable json)
  - `brand`: string|null
  - `last4`: string|null
  - `exp_month`: int|null
  - `exp_year`: int|null
  - `fingerprint`: string|null (重複検出したい場合のみ)
- `payout_bank_meta` (nullable json)
  - `bank_name`: string|null
  - `last4`: string|null
  - `country`: string|null
  - `currency`: string|null
  - `account_holder_type`: `individual` | `company` | null

## 実装ステップ
1. Migration 追加
- 対象: `database/migrations`
- `monetization_accounts` に `card_meta`, `payout_bank_meta` を `json()->nullable()` で追加。
- 既存データは nullable のため後方互換を維持。

2. Eloquent Model 更新
- 対象: `application/Models/Monetization/MonetizationAccount.php`
- `$fillable` に `card_meta`, `payout_bank_meta` を追加。
- `casts()` に `card_meta => 'array'`, `payout_bank_meta => 'array'` を追加。

3. Domain ValueObject 追加
- 対象: `src/Monetization/Account/Domain/ValueObject`
- `CardMeta`, `PayoutBankMeta` を追加（readonly + バリデーション）。
- `account_holder_type` は enum で制約する（例: `AccountHolderType`）。

4. Entity 更新
- 対象: `src/Monetization/Account/Domain/Entity/MonetizationAccount.php`
- コンストラクタ引数に `?CardMeta`, `?PayoutBankMeta` を追加。
- getter/setter（またはまとめ setter）を追加。
- 既存の `setBillingInfo(...)` と同様に、`setPaymentMeta(...)` のような集約更新メソッドを用意してもよい。

5. Factory 更新
- 対象: `src/Monetization/Account/Infrastructure/Factory/MonetizationAccountFactory.php`
- 新規作成時は `cardMeta=null`, `payoutBankMeta=null` で初期化。

6. Repository 更新
- 対象: `src/Monetization/Account/Infrastructure/Repository/MonetizationAccountRepository.php`
- `save()` に `card_meta`, `payout_bank_meta` の永続化を追加。
- `toDomainEntity()` で配列 -> ValueObject の復元を追加。

7. 更新ユースケース追加
- 新規ユースケースを `src/Monetization/Account/Application/UseCase/Command/...` に追加。
- 例: `UpdateMonetizationPaymentMeta`
  - 入力: `MonetizationAccountIdentifier` と `CardMeta`/`PayoutBankMeta`
  - 処理: 取得 -> 更新 -> save
- 注意: 既存実装には請求先情報更新ユースケースがないため、今回同様に Command を作る。

8. カードメタ同期（決済イベント起点）
- 起点: 決済ドメインの既存イベント（例: authorize/capture 成功イベント）。
- フロー:
  - イベント受信 -> MonetizationAccount を取得
  - `card_meta` 未登録なら Stripe から PaymentMethod メタ取得
  - `card_meta` を保存（必要なら差分更新も許可）
- 実装:
  - イベントハンドラを追加し、`UpdateMonetizationPaymentMeta` を呼ぶ。
  - イベントは重複配送され得るため、同一内容の再処理に耐える実装にする（冪等）。

9. 銀行口座メタ同期（Webhook 起点）
- 起点: Stripe Connect の external account 系 Webhook。
- フロー:
  - Webhook 検証 -> 対象 Connected Account を特定
  - `payout_bank_meta` が未登録なら作成、既存なら差分更新
  - `UpdateMonetizationPaymentMeta`（または専用 `UpsertPayoutBankMeta`）を呼ぶ
- 実装:
  - 署名検証とイベント ID ベース冪等化を必須にする。
  - Stripe を正、アプリを表示用キャッシュとして扱う。

10. テスト追加
- Unit:
  - `CardMeta`, `PayoutBankMeta` のバリデーション
  - Entity の更新挙動
  - Repository の save/load round-trip
- UseCase:
  - 正常更新、対象なし、部分更新（cardのみ/bankのみ）
- Integration:
  - migration 後の insert/select
  - Webhook 処理がある場合は payload から保存まで

## 同期方針（今回の決定）
- カードメタ:
  - 決済ドメインのイベントで同期する。
  - 未登録時は作成し、必要に応じて差分更新する。
- 銀行口座メタ:
  - Webhook で銀行口座登録ユースケース（または upsert ユースケース）を呼ぶ。
  - 未登録時作成 + 既存時更新の upsert で運用する。
- 共通:
  - 冪等化（重複イベント/Webhook対策）を必須とする。

## 変更対象ファイル一覧（想定）
- `database/migrations/*_add_payment_meta_to_monetization_accounts_table.php`
- `application/Models/Monetization/MonetizationAccount.php`
- `src/Monetization/Account/Domain/Entity/MonetizationAccount.php`
- `src/Monetization/Account/Infrastructure/Factory/MonetizationAccountFactory.php`
- `src/Monetization/Account/Infrastructure/Repository/MonetizationAccountRepository.php`
- `src/Monetization/Account/Domain/ValueObject/CardMeta.php`
- `src/Monetization/Account/Domain/ValueObject/PayoutBankMeta.php`
- `src/Monetization/Account/Domain/ValueObject/AccountHolderType.php`
- `src/Monetization/Account/Application/UseCase/Command/UpdateMonetizationPaymentMeta/*`
- `tests/Monetization/Account/...`
