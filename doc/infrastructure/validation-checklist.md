# 変更リスクに応じた検証票

[構成](../../infra/cloudformation/README.md)、[操作](operations.md)、[接続](pipeline-handoff.md)、[設計判断](design-decisions.md)から該当変更を選ぶ。
初回配備や従来の全項目消化をリファクタリングの完了条件にしない。静的PASSはAWS/Cloudflareでの成功の証明ではない。

## 今回の確認と未実施

- 静的: `task infra:validate`（cfn-lint＋保護/接続/操作テスト）、`task infra:operate -- config-check --environment infra/cloudformation/environment.example.json`。
- 机上: 下表の5通常操作を正本からたどり、値の取得元・担当・失敗時中止・復旧先を確認。
- 実環境: 対象accountを確認できる権限がなく未実施。環境が無いとは判定しない。AWS/Cloudflareへの書込なし。

| 操作 | 入口 / 正本 | 必要な確認 / 中止 / 復旧 |
|---|---|---|
| 通常配備 | Pipeline・接続台帳、operations §3 | 固定成果物、migration→API目的deployment/bake→worker→frontend。失敗で後続停止、部分成功は対象だけ復旧 |
| 基盤変更 | operations §2 のplan/execute | pause排出、live Outputs/state、nested review。state変化/中間重み/権限不足は中止、新planを再レビュー |
| 設定/Secret | environment inputs / SSM / Secrets、§4 | 非秘密と値を分離、新task反映・新旧互換確認、旧資格情報の失効。SecretだけのValkey変更を禁止 |
| 容量変更 | root/runtime inputs、§4 | 現revision/desired維持、release定義CPU反映、DB再起動/費用/提供version。置換は専用移行計画 |
| 障害復旧 | operations §5 | pause維持、最初のevent、正常revision、別DB/cacheの整合、再管理前の旧Outputs採用を禁止 |

## 実環境で変更リスクを確認する場合

| 変更 | 確認方法と期待結果 |
|---|---|
| 初回基盤 | 正しいaccount/4親、package bucket、CREATE/nested/終了保護、0task/DISABLED、秘密値が出力されない |
| state取込み | 2回目配備でBlue/Greenを反転しplan→update。revision/desired/3target/Schedulerが実現在値から戻らない |
| 競合/失敗 | plan後に検証環境で配備/容量変更しexecuteを拒否。中間重み、bake中、権限不足、stack更新中、waiter timeoutで後続を止める |
| integration設定 | SSMの新env名をreleaseへ注入しSQS/S3/画像URLを確認。TLS付きDATABASE_URL/REDIS_URLとCAは別に検証 |
| IAM/データ保護 | policy評価/安全な拒否試験で別repo/OIDC/PassRole/CF変更拒否、非公開DB/cache、書類がCDNに出ない、保持/暗号化 |
| API/worker | hook失敗/timeoutで旧API維持、bake alarm/欠損でrollbackを失敗扱い、draining/ジョブ再試行と副作用を確認 |
| 復元 | 別DB/cacheでTLS/権限/業務整合/RPO/RTO、旧資源保持と新書込後切戻し、import可否を確認。S3 version/DLQも必要な範囲 |
| Cloudflare/認証 | 変更がある場合のみ固定SHA/build/preview/deploy、token scope、DNS/SAN、Cookie/CSRF/OAuth/passkey成功・拒否 |
| 監視/費用 | 外部canaryの失敗/欠損、通知配送、Scheduler delivery/task/job別監視、retained資源/旧新併存費用 |

危険な失敗注入は承認した検証環境で行う。未確認をPASSにしない。次の非秘密記録をアクセス制限したrepo外保管庫へ保存する。

```text
変更ID / owner / UTC日時 / account・region / environment:
対象変更と選択した試験 / 未選択の理由:
status: 未実施 | PASS | FAIL | BLOCKED
backend/frontend/workflow SHA / digest / task・deployment・Worker version:
操作入口 / exitCode / 事前状態 / 期待 / 実観測:
中止閾値 / 復旧責任者 / 切戻し条件 / RPO・RTO:
plan/result・マスク済証跡の保管場所 / 未検証項目の確認方法:
```

Secret/token/Cookie/個人情報/queue payload全体を保管しない。実値の公開Issue記載も行わない。
