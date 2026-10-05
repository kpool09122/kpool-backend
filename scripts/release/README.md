# Release tooling

運用手順・Variables／Secretキー・手動入力・固定 source_attempt 再実行・未接続frontend／実環境ゲートは [backend-release.md](../../doc/infrastructure/backend-release.md) を参照。

- `contract.py`: 入力・非秘密env・ECS task definition・公開CAの契約。
- `github_source.py`: mainからのSHA解決、所有を照合した固定run／attempt artifact読取。
- `aws_backend.py`: 実AWS CLI（JSON引数）とCF Outputs／SSM設定、migration／API／worker操作。
- `deployment.py`／`runner.py`: 有限poll、revision判定、後続停止・live観測。
- `records.py`／`cli.py`: immutable manifest／journal、workflowのplan／build／publish／deploy入口。
- `fixtures.py`, `test_*.py`: 明示されたfake AWS外部境界。実botocoreモデルでrequestを検証する。

```sh
ACTIONLINT=/tmp/kpool-release-bin/actionlint CFN_VENV=/tmp/kpool-cfn-tools bash scripts/release/run.sh check
```

AWS・Cloudflareへの実変更を行わないoffline command。`cli.py publish/deploy` は実環境でのmutation入口なので、ローカルテストでは直接実行しない。
