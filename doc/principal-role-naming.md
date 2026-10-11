# Principal ロールの名称と初期化

ロール保存値・API値・英語表示は UpperCamelCase、PHP定数識別子は UPPER_SNAKE_CASE とする。ポリシー名・Action値・ResourceType値はこの規則の対象外。

| コンテキスト | ロール | 責務 |
| --- | --- | --- |
| Account | Owner | Account所有者。招待・所属管理などの既存条件を維持 |
| Account | Administrator | Account内の管理担当。Owner専用の申請作成権限は持たない |
| Account | Operations | サービス全体の運用担当資格。Operator付与時に確認 |
| Wiki | Operator | 全体のWiki運用。Principalグループ管理権限を自動付与しない |
| Wiki | Administrator | Account内のWiki Principal所属管理。Account Ownerの初期所属先 |
| Wiki | SeniorCollaborator / AgencyActor / TalentActor / Collaborator / None | 既存の編集・スコープ条件を維持 |
| SiteManagement | Operator | 全体のお知らせ・お問い合わせ運用 |
| SiteManagement | General | 自分のお問い合わせ閲覧のみ |

SiteManagementのAccount内Administratorは後続Issue #709で追加する。本変更では作成しない。

## 新規初期化

新規の環境で `task migrate-fresh` を実行する（既存データを削除するため既存環境では使用しない）。個別初期化は次のSeederを順番に実行する。

1. `task seed class='Database\Seeders\AccountAuthorizationSeeder'`
2. `task seed class='Database\Seeders\SystemPolicySeeder'`
3. `task seed class='Database\Seeders\SystemRoleSeeder'`
4. `task seed class='Database\Seeders\SiteManagementAuthorizationSeeder'`

Wiki Administratorは `GLOBAL_PRINCIPAL_GROUP_MANAGE`、Wiki Operatorは従来の全体Wiki運用ポリシーを持つ。SiteManagementの既存ポリシー名 `administrator` / `general` は変更しない。

## 全体運用資格の付与・剥奪

```
task operations:grant email=operator@example.com
task wiki:operator:grant email=operator@example.com
task site-management:operator:grant email=operator@example.com

task wiki:operator:revoke email=operator@example.com
task site-management:operator:revoke email=operator@example.com
task operations:revoke email=operator@example.com
```

Artisanの名称も `wiki:operator:grant|revoke` / `site-management:operator:grant|revoke`。付与は既存のOperations資格・AccountとIdentityの所属確認を維持する。剥奪は既存のAccount単位の運用グループ削除を維持し、Operations剥奪後でも実行できる。

全体運用グループは `Operations Wiki Operators` / `Operations SiteManagement Operators`。WikiのAccount内管理グループは `Wiki Administrator`。Account Ownerの初期所属と最後のWiki Administratorを削除できない保護を維持する。

## 境界と破壊的変更

旧CLI・旧Grant/Revokeクラスのalias、旧ロールの併存、移行migration/backfill、旧ID・所属を引き継ぐ処理は提供しない。既存データを新名称へ移行する用途でSeederを使わない。

Wikiの現行 `UpdatePrincipalGroupMembers` はAccount所属と `PRINCIPAL_GROUP_MANAGE` の認可、最後のAdministrator保護を確認するが、Operatorグループの所属変更を個別に禁止する判定はない。Operator自身にこのポリシーは付与されないものの、Account内Administratorは同一AccountのOperatorグループを更新可能。この境界強化は後続Issue #710の対象であり、本Issueでは権限の意味を変えない。
