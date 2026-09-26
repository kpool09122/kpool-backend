# Project Codex Instructions

## PHP コマンドの実行

- PHP の検証・依存管理は `Taskfile.yml` の `task` コマンドを使う。ホストの PHP はプロジェクトの要求バージョンと異なる場合がある。
- コード整形は `task cs-fix`、静的解析は `task phpstan`、テストは `task test` を使う。対象を絞る場合は `task test filter=TestClassName`、DB 不要のテストは `task test-no-db filter=TestClassName` を使う。
- 一括検証は `task check`、依存のインストールは `task install`、その他の Composer 操作は `task composer -- <引数>` を使う。

## TypeSpec And OpenAPI

- TypeSpec で OpenAPI の nullable を表現する場合は、`field?: string` だけにせず `field?: string | null` のように `| null` を明示する。
- Laravel の Request で `nullable` な query/body パラメータを TypeSpec に追加・更新する場合も、OpenAPI 生成結果が null 許容になるよう `| null` を付ける。
