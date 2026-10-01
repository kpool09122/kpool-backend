# 本人のサービス退会（Issue #643）

`DELETE /api/identity/identities/me` は ActorContext の本人だけを対象にする。削除対象IDは入力に使わない。成功は本文なしの204。このDELETEルートだけにLaravelのCSRF保護を適用する。先に認証不要の `GET /api/identity/auth/csrf-token` を呼び、レスポンスのセッションCookieと `XSRF-TOKEN` Cookieを保持する。このGETはデータを変更せず、204・`Cache-Control: no-store` を返す。ブラウザは `XSRF-TOKEN` の値をURLデコードして `X-XSRF-TOKEN` に送る。Laravelの暗号化・検証を使い、既存のAPIセッションCookieの形式は変えない（Laravelの同一オリジン検証も適用）。POSTの `_method=DELETE` も保護対象。検証失敗は419・`code=csrf_token_mismatch`。他のAPIルートのミドルウェアは変更しない。通常ログイン不足は401・`code=authentication_required`、直近再認証不足は401・`code=recent_authentication_required`、退会条件不成立は403・`code=identity_withdrawal_not_allowed`。

## 直近再認証

`RECENT_AUTHENTICATION`（保存値 `recent_authentication`）をパスキー操作と退会で共用する。同じIdentityと同じログインセッションに結び付け、検証成功時から600秒で失効する。参照では消費・期限延長を行わない。パスキー登録済みなら既存パスキー、未登録なら本人に連携済みのソーシャル認証を使用する。

ソーシャル再認証開始の必須query `returnTo` は `passkeys` または `withdrawal`。サーバーがそれぞれ `/settings/passkeys?stepUp=complete`、`/settings/withdrawal?stepUp=complete` に変換し、OAuthセッションに保存する。DomainのStepUpReturnDestinationは用途の列挙だけを担い、パスの変換はApplication Serviceのinterfaceを介したInfrastructureアダプターで行う。コールバックは保存済みの戻り先を使用する。OAuthセッションも開始元ログインセッションに結び付き、別セッションでは取得できない。

## 退会条件と処理境界

本人のAccount Principalを全件取得し、実際の所属Accountのカテゴリ・種別を確認する。GENERALの個人はOwnerでも許可する。GENERALの法人はシステムOwnerロールを持つグループの所属者を拒否する。他のOwnerの人数・グループ表示名は判定に使わない。AGENCY、TALENT、種別未設定、所属なしは拒否する。個人へのメンバー招待は既存のInviteMemberで禁止されている。

WithdrawIdentityActionがDBトランザクションを管理する。Identityをロックし、許可リストのIdentityアーカイブを保存して同期IdentityWithdrawingイベントを発行する。Account側で所属・Account・グループ所属・ロール付与をロックして判定し、各コンテキストのハンドラーがアーカイブと削除を行う。個人Account削除前には同期AccountDeletingイベントでWiki・Monetizationの外部キーのない参照を処理する。最後にIdentityを物理削除する。例外時は全アーカイブと削除をロールバックする。

## アーカイブ

全テーブルの管理日時は共通の `archived_at` のみ。Query Builderで明示した列だけをinsertするため、自動タイムスタンプやモデルへの項目追加による自動転記はない。

| テーブル | 保存する列 |
| --- | --- |
| archived_identities | identity_id（主キー）、language、identity_created_at、archived_at |
| archived_accounts | account_id（主キー）、account_category、account_type、archived_at |
| archived_principals | identity_id、principal_type、principal_id、account_id、archived_at |

Principalの主キーは `(principal_type, principal_id)`。identity_idの外部キーはarchived_identitiesのみを参照し、稼働テーブルへの外部キーはない。Account/Wiki Principalを削除前にそれぞれ記録する。Wiki未登録ならWiki明細はない。法人所属者の退会では法人Accountを残し、Accountアーカイブを作らない。

氏名、連絡先、画像、書類、外部サービス利用者ID、認証情報、トークン、IP、自由記述はアーカイブしない。内部IDから公開投稿などと照合できるため、匿名データではない。

## 調査した削除・保持範囲

以下はmigrationsと永続化・読み取り実装を確認したうえでの方針。外部キーの動作だけを保持要件とは扱わない。

| 対象 | 退会時の扱い |
| --- | --- |
| identities / identity_social_connections / passkey_users / passkey_credentials | 本人を物理削除。認証レコードはIdentityからのFK連鎖削除 |
| account_principals / wiki_principals | 本人の全Principalを種別別にアーカイブして削除 |
| account_principal_group_memberships / wiki_principal_group_memberships | PrincipalのFK連鎖削除 |
| contribution_point_histories / contribution_point_summaries / promotion_histories / demotion_warnings | 本人の貢献ポイント・昇降格管理情報を削除。公開編集履歴とは区別する |
| site_management_users | 本人のサービス利用者レコードを削除 |
| contacts / contact_replies | 本人の問い合わせとその返信、本人が作成した返信を削除。Identityへの参照はFKではないため明示削除 |
| invitations | 本人が送った招待はIdentityから連鎖削除。本人が受諾した他人の招待はaccepted_by_identity_idがNULL |
| accounts | 個人のみアーカイブ・削除。法人は継続利用 |
| account_documents / account_category_change_requests | 個人Accountから連鎖削除。書類実体はコミット後削除 |
| account_principal_groups / wiki_principal_groups | 個人Accountから連鎖削除。所属・ロール付与も連鎖削除 |
| account_roles / account_policies / wiki_roles / wiki_policies | 削除する個人Account所有の行を明示削除。account_idをNULLにするとシステム権限になるためNULL化しない |
| official_certifications | 削除する個人Accountの認証情報を明示削除 |
| wikis.owner_account_id | 削除する個人Accountへの所有参照をNULL化 |
| monetization_accounts / monetization_registered_payment_methods / monetization_payout_accounts / settlement_schedules / settlement_batches / transfers | 個人Accountから連鎖削除 |
| invoices / invoice_lines / payments | 個人AccountのMonetization Accountに属する請求・決済データを明示削除。invoice_linesはInvoiceから連鎖削除。法人の請求・決済データは保持 |

### 公開投稿・編集履歴

共有Wiki本文、下書き、スナップショット、画像と画像ファイル、動画リンク、Wiki基本情報、wiki_historiesは保持する。ユーザーが投稿本文に記載した内容はこの退会処理では編集・除去しない。

wikis / draft_wikis / wiki_snapshotsのeditor_id・approver_id・merger_id・source_editor_id、wiki_historiesのactor_id・submitter_id、wiki_images / draft_wiki_images / wiki_image_snapshotsのuploader_id等、image_deletion_requestsのreviewer_idは履歴上の内部Principal IDとして保持する。これらは元から稼働PrincipalへのFKではなく、削除済みIDはarchived_principalsのwiki明細と対応する。現行リポジトリは内部IDから画像・履歴をロードできる。稼働Principalとグループ所属は削除されるので、そのIDで権限を得ることはできない。アーカイブのPrincipal明細自体はサービス登録記録であり、閲覧・投稿の実績を意味しない。

account_affiliations / account_delegations / affiliation_grantsはGENERALでは作成できない既存認可条件に基づき本処理の対象外。AGENCY/TALENTをGENERAL向け処理で削除しない。アナウンスには本人への参照がなく保持する。

## コミット後と再登録

コミット後に全ログインセッションの世代を更新し、現在のセッションをログアウトする。世代更新が失敗してもログアウトを試みる。セッション無効化・ログアウト・キャッシュ・ファイルの各障害を記録し、コミット済み退会は204を返す。Actionがコミット後にRequest属性へ付ける内部印がある場合に限り、外側のLaravelセッション保存失敗もログに記録して204を保持する。通常ルートのセッション保存失敗は従来どおり例外となる。DB上のIdentityは既に削除されているため、他端末のLaravel認証も本人をロードできない。Actor/Account/Wikiキャッシュ、現在セッションのAccount選択、プロフィール画像・本人確認書類の掃除はコミット後に行う。ファイル・キャッシュの失敗はログで追跡し、DB削除を取り消さない。

WebAuthn challenge、回復キー、メール認証、ソーシャル連携の一時データは既存TTLで失効する。回復完了・連携完了は稼働Identity（および必要なPasskey User）を再確認するので、削除済みIdentityを復元できない。他端末のAccount選択キャッシュが残っても削除済みIdentityでは認証できない。

同じメールアドレス・ソーシャルアカウントによる新規登録は既存の登録フローで可能。新しいIdentity IDが発行され、旧Identity・Principal・権限・回復情報は引き継がない。
