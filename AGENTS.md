# Project Codex Instructions

## TypeSpec And OpenAPI

- TypeSpec で OpenAPI の nullable を表現する場合は、`field?: string` だけにせず `field?: string | null` のように `| null` を明示する。
- Laravel の Request で `nullable` な query/body パラメータを TypeSpec に追加・更新する場合も、OpenAPI 生成結果が null 許容になるよう `| null` を付ける。
