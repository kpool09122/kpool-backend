# Release tooling

運用手順・Variables／Secretキー・手動入力・固定 source_attempt 再実行・Workers配備／実環境ゲートは [backend-release.md](../../doc/infrastructure/backend-release.md) を参照。

- `contract.py`: 入力・非秘密env・ECS task definition・公開CAの契約。
- `github_source.py`: mainからのSHA解決、所有を照合した固定run／attempt artifact読取。
- `aws_backend.py`: 実AWS CLI（JSON引数）とCF Outputs／SSM設定、migration／API／worker操作。
- `frontend.py`: Workers設定、検証済みartifactの受渡し、Cloudflare metadataによるversion確認・配備記録。
- `deployment.py`／`runner.py`: 有限poll、revision判定、後続停止・live観測。
- `records.py`／`cli.py`: immutable manifest／journal、workflowのplan／build／publish／deploy入口。
- `fixtures.py`, `test_*.py`: 明示されたfake AWS外部境界。実botocoreモデルでrequestを検証する。

```sh
mkdir -p /tmp/kpool-release-bin
GOBIN=/tmp/kpool-release-bin go install github.com/rhysd/actionlint/cmd/actionlint@v1.7.12
ACTIONLINT=/tmp/kpool-release-bin/actionlint CFN_VENV=/tmp/kpool-cfn-tools bash scripts/release/run.sh check
```

AWS・Cloudflareへの実変更を行わないoffline command。`cli.py publish/deploy` は実環境でのmutation入口なので、ローカルテストでは直接実行しない。

workflow検証はCI・ローカルともactionlint v1.7.12を使用する（ソースからのインストールにはGo 1.25以降が必要）。
同版には公式GitHub-hosted runnerのUbuntu 26.04ラベルが未収録のため、`.github/actionlint.yaml` の追加ラベル設定で `ubuntu-26.04` / `ubuntu-26.04-arm` だけを許可する。
この設定はself-hosted runnerへの変更ではなく、検証ツールの互換性設定。その他の不明なラベルは引き続き検証エラーになる。

## PR #732 のランタイム更新確認

Pythonの検証環境は3.15に統一し、`cfn-lint==1.47.0` のインストール後に上記のoffline checkを実行する。
旧Pythonの仮想環境は[インフラの入口](../../infra/cloudformation/README.md#検証操作の入口)の手順で作り直す。
ローカルのLinux ARM64 / Python 3.15.0環境では、依存インストール・`pip check`・リリース86件・インフラ96件（任意DB統合1件skip）・cfn-lint・actionlint v1.7.7が成功した。
ネイティブ依存をソースからビルドする場合はC/Rustのビルド環境が必要。マージ前には変更をpushしたheadのinfrastructure CI成功も確認する。

2026-10-21（日本時間）の確認時点では、Nodeの最新公開版は26.12.0、setup-nodeはv7.1.0。
[Node公式告知](https://nodejs.org/en/blog/vulnerability/october-2026-security-releases)は26.xなどに最高HIGHの修正を予定している。
PR担当者はマージ前に公開状況を再確認し、修正版の公開から24時間以内を目安にNodeの固定版を更新する。
公開前にマージする場合もこの追跡は継続し、更新時にはTypeSpecと全6 APIのZod生成を実行する。
Node 26.12.0 / pnpm 12.4.2では、固定lockfileからのインストール・TypeSpecコンパイル・openapi-zod-client 1.18.3による全6 APIの生成がローカルのLinux ARM64環境で成功した。

setup-node v7.1.0は `smol-toml@1.7.1` を同梱し、[GHSA-r4xh-jqrq-34v2](https://github.com/advisories/GHSA-r4xh-jqrq-34v2)（Medium、修正版1.9.0）の対象。
Zod workflowは `node-version` の直接指定、release workflowはJSONの `source/package.json` 指定で、現在はTOMLを入力しない。
PR担当者はマージ前とsetup-node更新時に公式releaseを確認し、smol-toml 1.9.0以降を実行用distへ同梱した版が公開され次第、固定SHAを更新する。
TOMLのversion-fileを採用する場合は、その更新を先に完了する。
