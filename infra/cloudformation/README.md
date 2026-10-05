# 本番インフラの入口

k-pool の AWS（東京）と Cloudflare の構成・設定・運用をここからたどる。

- 通常配備・基盤更新・Secret 更新・容量変更・障害復旧: [運用手順](../../doc/infrastructure/operations.md)
- AWS / Cloudflare / GitHub / フロントとの接続: [接続台帳](../../doc/infrastructure/pipeline-handoff.md)
- 変更の理由・既存環境への移行・未検証事項: [設計判断](../../doc/infrastructure/design-decisions.md)
- 変更リスクに応じた確認と記録: [検証票](../../doc/infrastructure/validation-checklist.md)
- アプリ固有の契約: [コンテナ](../../doc/infrastructure/container-runtime.md)、[queue](../../doc/infrastructure/queue-operations.md)、[S3](../../docs/s3-storage.md)

## 全体像と管理責任

```text
管理者の短期 AWS 認証
  bootstrap (OIDC / 配備ロール / CloudFormation 実行ロール)
    ↓ 実行ロールを自動取得
  root ─┬─ network (VPC / public・data 各2AZ / SG)
        ├─ data (非公開 PostgreSQL / Valkey / 管理Secret)
        └─ storage (画像・書類別S3 / 画像専用CloudFront / SQS・DLQ)
    ↓ Outputs を自動取得
  integration (app・migration Secretの保存先 / 非秘密SSM / account予算)
    ↓ root + integration Outputs を自動取得
  runtime (ECR / ECS / ALB / 用途別IAM / hook / alarms / Scheduler)
    ↓ 読取専用Outputs
  GitHub release Pipeline → migration → API BLUE_GREEN → worker → Cloudflare frontend
```

管理者は CloudFormation の基盤変更、Secret 登録、DNS・復元・容量を担当する。
Pipeline はリリース用 task revision とアプリ切替だけを担当し、CloudFormation・ALB・DB基盤を変更しない。
Cloudflare の Worker/DNS/token は AWS スタックの外部契約で、設定先と担当は接続台帳にまとめる。
初期 task は0、Scheduler は DISABLED。bootstrap image は稼働用ではない。

4親スタックを残す理由は、信頼の起点となる bootstrap、長期データ、Secret/非秘密設定、頻繁に変わる compute を分離するため。
root 内の子は個別に配備しない。手動転記のための分割ではなく、操作ツールが依存を解決する境界である。

## 設定の正本

- 非秘密の環境入力は **リポジトリ外の1つの environment JSON**。雛形は [environment.example.json](environment.example.json)。例は架空の値で適用しない。
- Parameter の型・制約・既定値は各 `.yaml` が正本。環境JSONには必須の外部入力と変更したい任意入力だけを書く。既存環境の任意入力の省略値は現在の CloudFormation Parameters を維持する。
- [contracts.json](contracts.json) は操作ツールが読むregion・親の適用順・親間の接続だけを持つ。子の接続とParameter/Output定義はテンプレート、Pipelineの消費項目・担当は接続台帳が正本。未使用の全Output在庫を複製しない。
- リソースID・endpoint・ARNは実 Outputs、稼働revision・capacity・転送先は ECS/ALB/Scheduler が正本。environment JSON にコピーしない。
- Secret 値は Secrets Manager、Cloudflare token/App鍵は GitHub Environment Secrets。環境JSON・plan・Outputs・ログへ入れない。

`projectName` は bootstrap/integration/runtime の名前・IAM範囲、`resourcePrefix` は既存データ基盤の物理名の接頭辞。
用途が異なるため既存名称を一括改名しない。`stacks.root` は20文字以内、その他3親名は `${projectName}-<役割>`。
この対応をツールで検査し、bootstrap の基盤名・package bucket・queue・hook object ARN・証明書zoneは導出する。

## 検証・操作の入口

```sh
task cfn:install                 # 初回のみ: Python 3.13 / pinned cfn-lint
task infra:validate              # AWS認証不要。CIも同じ実装を実行
task infra:operate -- --help
```

`CFN_PYTHON` / `CFN_VENV` でPythonと仮想環境を選べる。部分検証は `task cfn:lint` / `task cfn:test`。CIも `run.sh check` を直接実行する。旧 `cfn:check` / `validate.sh` は削除し、通常入口へ統一した。
AWSを操作する `plan` / `execute` は明示操作であり、検証やCIからは呼ばれない。

## 基盤の保護と制約

VPC は10.20.0.0/16、publicは .0.0/24 と .1.0/24、dataは .10.0/24 と .11.0/24。
data にinternet既定経路はなく、task はpublic subnet + 明示public IPで必要な外向きHTTPSへ接続する。
DB/cacheは用途別SGのみ、API受信はALB SGのみ。test listenerはhook SGのみ。
公開画像もS3自体は非公開・OAC経由。非公開書類は別bucketで認可後のAPI配信、CDNには接続しない。

RDS は暗号化PostgreSQL16・gp3・7日backup・削除/置換Snapshot・削除保護、初期Single-AZ。
Valkeyはprivate/TLS/password認証・7日snapshot。S3はversioning、S3/SQS/Secret/cache/log/ECR等の保持policyを維持する。
Retainは復元や無期限保存の保証ではない（SQS4日、DLQ14日、ログ既定30日）。廃止時も保持物の費用は継続する。
API/worker/migrationの用途別roleとDML/DDLのDBユーザーを分離し、RDS master Secretを通常taskに渡さない。
復旧と残余リスクは運用手順・設計判断を参照。実環境の作成成功・権限充足・rollback成功は静的検証だけでは保証しない。
