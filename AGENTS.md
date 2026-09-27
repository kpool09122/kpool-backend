# Project Codex Instructions

## PHP コマンドの実行

- PHP の検証・依存管理は `Taskfile.yml` の `task` コマンドを使う。ホストの PHP はプロジェクトの要求バージョンと異なる場合がある。
- コード整形は `task cs-fix`、静的解析は `task phpstan`、テストは `task test` を使う。対象を絞る場合は `task test filter=TestClassName`、DB 不要のテストは `task test-no-db filter=TestClassName` を使う。
- 一括検証は `task check`、依存のインストールは `task install`、その他の Composer 操作は `task composer -- <引数>` を使う。

## TypeSpec And OpenAPI

- TypeSpec で OpenAPI の nullable を表現する場合は、`field?: string` だけにせず `field?: string | null` のように `| null` を明示する。
- Laravel の Request で `nullable` な query/body パラメータを TypeSpec に追加・更新する場合も、OpenAPI 生成結果が null 許容になるよう `| null` を付ける。

## PHP の依存オブジェクトの命名とインポート

- Service / Repository の依存変数・プロパティ名は、型名から `Interface` を除き、先頭を小文字にした名前を使う。例: `SocialLinkingSessionStorageServiceInterface $socialLinkingSessionStorageService`、`IdentityRepositoryInterface $identityRepository`、`AuthServiceInterface $authService`。型を別名でインポートしている場合は、その別名を基準にする。
- 単一の依存オブジェクトを `$sessions`、`$identities` のような複数形や、役割を省略した名前で表さない。複数形はコレクションなど実際に複数の値を持つ変数に使う。
- クラス・インターフェース・例外は `use` でインポートし、コード内では短い名前を使う。PHPDoc の `@throws`、`@param`、`@return`、`@var` も同じ方針とする。同名が衝突する場合は役割が分かる別名を付けてインポートする。
- PHPStan のカスタムルールで依存オブジェクトの命名と完全修飾名の直接記述を検出する。

## トランザクションと並行処理

- DB トランザクションは原則として Action で管理する。
- 現段階では、ごく短い時間差の並行実行だけを想定した整合性保証の仕組みを追加しない。Lua による一括処理や専用ロックは、具体的な要件がある場合に検討する。
