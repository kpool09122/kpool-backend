# Backend リリース Pipeline

実装入口は `.github/workflows/release.yml`。backend 単独で validation／production ARM64 build → ECR → VPC migration → ECS native BLUE_GREEN API → worker → HTTPS health／SQS 属性確認を実行する。frontend checkout は不要。基盤作成・CloudFormation 更新・DB rollback は実行しない。

コードの実装・ローカル検証と本番適用は別である。現時点で AWS／Workers への配備、hook の実動作、SQS canary job 処理は未確認。

## 初回設定

backend の production Environment に許可 branch `main` を設定する。途中承認待ちを設けない運用では required reviewers を設定しない。workflow 変更は branch protection／レビューで保護する。統合入口の production concurrency は cancel-in-progress=false。基盤の管理者操作との排他・drift 反映は既存 [operations.md](operations.md) に従う。

Environment Variables:

| 名前 | 内容 |
| --- | --- |
| AWS_ACCOUNT_ID | 対象 account（12桁） |
| AWS_DEPLOY_ROLE_ARN | bootstrap DeploymentRoleArn と一致する OIDC role |
| LIVE_FRONTEND_SHA | 現在稼働 frontend の完全40桁 SHA（backend compatibility gate） |
| RDS_CA_PEM | 管理者が公式 RDS 東京 truststore から取得した公開 PEM（48KB以下）。秘密情報を入れない |
| RDS_CA_SHA256 | 上記 PEM の UTF-8 bytes の SHA256。改行も一致させる |
| BACKEND_RELEASE_CONFIGURATION | 以下の非秘密 JSON。stack 名は実環境の正本から登録 |
| FRONTEND_RELEASE_CONTRACT | frontend seam 用。現時点では未接続なので登録しても all/frontend は変更前に FAIL |

設定 JSON の形（括弧付きの文字は実値に置換する。ARN・account・SHA の実行時既定値はない）:

```json
{
  "paused": false,
  "region": "ap-northeast-1",
  "account_id": "<account>",
  "deployment_role_arn": "<bootstrap DeploymentRoleArn>",
  "stacks": {"bootstrap": "<stack>", "root": "<stack>", "integration": "<stack>", "runtime": "<stack>"},
  "hook_code_sha256": "<approved Lambda CodeSha256>",
  "runtime_environment": {
    "APP_URL": "<runtime ApiUrl>",
    "FRONTEND_URL": "https://<live frontend origin>",
    "CACHE_STORE": "redis",
    "SESSION_DRIVER": "redis"
  },
  "secret_contract": {
    "DATABASE_URL": "verify-full:/etc/ssl/certs/kpool-rds-ca.pem",
    "REDIS_URL": "tls:db0"
  },
  "app_secret_keys": [],
  "compatibility": {
    "approved": true,
    "backend_sha": "<resolved release SHA>",
    "live_frontend_sha": "<LIVE_FRONTEND_SHA>",
    "migration_policy": "expand-contract",
    "evidence": "<reviewed DB/queue/API/frontend compatibility evidence URL>"
  }
}
```

SSM integration RuntimeConfigParameterName の String JSON を読み、runtime_environment の許可キーだけを追加する。アプリの DATABASE_URL／REDIS_URL 契約を再利用する。Secret の中身を Pipeline が検証済みと扱わない。管理者が URL の TLS／CA／DB0、DBユーザー権限、Secret 登録を確認し、secret_contract を宣言する。実際の接続は migration・hook・起動時 health で確認する。

ECS は integration AppSecretArn／MigrationSecretArn の `APP_KEY`, `DATABASE_URL`, `DB_USERNAME`, `DB_PASSWORD` を JSON-key ARN 参照で注入する。API／worker は AppSecret の `REDIS_URL` と root CacheSecretArn の `password` を追加する。REDIS_URL は application user・TLS・DB0・現在の cache password と整合させる。追加アプリ Secret は許可キーを app_secret_keys に列挙する。migration へ追加アプリ Secret／cache secret は渡さない。RDS 管理ユーザーを使わない。

DATABASE_URL の query に `sslmode=verify-full&sslrootcert=/etc/ssl/certs/kpool-rds-ca.pem` を含める。公開 CA は checksum／OpenSSL 構文を検証し BuildKit mount から既存 production target に同梱する。Secret 値は build に渡さない。通常のローカル container build は CA mount 省略可能。

今回の基盤定義追加は既存 bootstrap role の scoped 読取（SSM String、project-prefix CloudWatch alarms、deployment-hook Lambda metadata、既存 work queue 属性）と runtime bootstrap task の writable volume のみ。API／worker／migration／scheduler の readonly root は維持する。runtime task 雛形と Dockerfile VOLUME により `/tmp`, `storage`, `bootstrap/cache` の所有者・mode を継承する。**管理者による既存 stack への反映が必要**。Pipeline 自身は反映しない。ExternalCanaryAlarmName は `${ProjectName}-` prefix 内で設定する（この scoped IAM の対象）。既存 ECS revision 読取／用途別 PassRole 制約は維持する。

## 手動入力と配備

- `target=backend`、`mode=deploy`。`backend_ref=main` または main の履歴に存在する完全 SHA。plan 作成時に SHA を解決し、build／配備は同じ SHA と artifact を使う。production control code は起動元 workflow SHA に固定し、アプリ source SHA を control code として実行しない。
- 初回だけ `initial=true`。API と worker がともに desired=0 の場合だけ 1 にする。通常は `initial=false` で各 service の現在希望数を維持する。
- `paused=true`、unstable stack/service、未設定 hook/alarm/bake、CA／compatibility 未設定、異なる account／role／digest は変更前に停止する。ECR 公開前にも非 image preflight を行う。
- 検証は Taskfile の install、非破壊 cs-check、phpstan、test を使う。ARM runner で既存 production target を build し、archive SHA256 と platform を記録。公開 job は archive を検証して ECR へ公開し、ECR 応答の digest を以後の全用途へ渡す。
- migration は既存 public subnet＋専用 SG、Fargate、public IP ENABLED で実行。DB は非公開。単一 task ARN／task definition／exitCode=0 が必要。停止コード欠落・起動失敗・10分 timeout は API を止める。
- API は新 service deployment ARN を発見し、その target service revision の task definition を照合。SUCCESSFUL、5分以上の bake、target traffic weight=100、希望数／pending=0、source tasks=0、live task definition の一致が必要。旧 stable revision、rollback、wrong revision、25分 timeout は成功にならない。
- API 成功後に worker を更新。対象 revision と希望数・COMPLETED を10分以内に確認。最後に HTTPS `/up`（redirect 禁止）と SQS identity／attributes を読む。**属性読取は job 処理証明ではない**。承認済み canary の投入・完了・DLQ 確認は実環境確認に残る。

## 記録・再実行・手動復旧

artifact は run／attempt ごとに immutable な名前で保存する（90日保持、image archive は14日）。release-plan、backend-image の publication manifest、backend-record の deployment manifest／append-only event files／journal、release-result を保存。summary と record に source SHA、control SHA、image digest、task ARN、API deployment ARN、stage 結果、観測時点の API／worker revision と希望数が残る。worker 失敗でも API の成功・live revision を消さない。

SIGTERM／例外では finally で live state を照会し、観測不能も記録する。全 upload step は always 条件。runner 消失／強制停止時には最後の upload／live 照会まで保証できないので、残った immutable intent／manifest と ECS live state を管理者が照合する。AWS 応答中断後に latest SHA へ読み替えたり、自動で DB を戻したりしない。

再実行は **source_run と source_attempt（既定1）の組を指定**する。対象 artifact 名を完全一致で選び、最新 attempt を探索しない。GitHub run の backend repository／workflow_dispatch／main／release.yml 所有と記録 environment／source／release ID／digest を確認。失効 artifact、未記録 migration、実環境との不一致は fail closed。

backend-record はアップロード時の `records/` 配下と従来のルート直下の記録を読み取る。同名記録の重複・両形式の衝突は停止する。manifest は deployment manifest → backend-record の `manifest.json` → backend-image の publication manifest の順に選び、resume／rollback の run も記録元に指定できる。

| 操作 | 入力／安全上の意味 |
| --- | --- |
| 同じ digest の backend 再配備 | `mode=redeploy`, `source_run=<元run>`, `source_attempt=<元attempt>`。元 migration success が必要。build／ECR push／migration は繰り返さず、同じ digest で新 release task revision を登録し API→worker を配備 |
| API成功／worker失敗の再開 | `mode=resume-worker` と元記録。記録の API success と live API task ARN 一致を確認し worker のみ更新。migration／API更新なし |
| 手動 API 復旧 | `mode=rollback-api` と戻したい **記録済み release**。既存 task ARN／digest／role の所有を確認して同じ native BLUE_GREEN gate を通す。worker／DBを戻さない |
| 手動 worker 復旧 | `mode=rollback-worker` と選んだ release 記録。API／migrationを実行しない。現在希望数を維持 |
| GitHub の Re-run all jobs | attempt1 の固定記録から redeploy へ切り替える。digest／migration success 記録がなければ停止。検証失敗のみの回復は、記録の SHA を明示した新 deploy を選ぶ |
| migration 応答中断／成功不明 | 自動再実行しない。task ARN／exitCode／DB migration 台帳を照合。新しい deploy を明示する前に DB 担当が安全性を判断 |

過去 release の replay／rollback と GitHub Re-run all jobs でも、現在稼働する frontend の完全40桁 SHA を `LIVE_FRONTEND_SHA` に設定する。記録済み backend SHA と現在の frontend SHA に対応した compatibility 証跡を設定し、配備前の gate で照合する。frontend 失敗を理由に backend／DB を戻さない。ECR digest の保持と90日より長い記録の保管は運用側で管理する。

## frontend seam（未接続）

`all`／`frontend` は現在明示 FAIL する。frontend AWS job の起動はなく、backend build／ECR／migration にも進まない。Workers アダプター／binding 等の #427 契約を未確認のまま完了扱いしない。

接続契約は `repository=kpool09122/kpool-frontend`, `workflow_sha=<trusted full SHA>`, `source_sha=<full SHA>` と、検証済み artifact ID／checksum、Worker version、production environment を持つ。GitHub reusable workflow の `uses` は動的式を使えないので、workflow_sha を入力で自由に実行せず、レビュー済みの完全 SHA を入口 YAML の static uses に固定して接続する。全検証／build が成功するまで両配備を止め、backend→frontend 順、frontend-only では AWS job を不要にする。

private frontend は対象限定 Contents:read の短命 GitHub App token で ref／checkout する。backend production の SOURCE_APP_ID／SOURCE_APP_PRIVATE_KEY から生成し、frontend へは必要な source token と CLOUDFLARE_API_TOKEN だけを明示渡しする（inherit 禁止、AWS secret／OIDCなし）。public repository であれば不要な App 認証を増やさない。Actions policy／private reusable access を確認する。現在その実装・外部 Secrets／Workers deployment はゲートとして残る。frontend-only retry は元 frontend SHA／artifact／Worker version の記録を使い、backend runner を呼ばない。

## ローカル確認

既存 CF tool venv と actionlint を使う。再インストールは不要。

```sh
ACTIONLINT=/tmp/kpool-release-bin/actionlint CFN_VENV=/tmp/kpool-cfn-tools task release:check
```

これは release の公開 API／state machine／AWS transport fixture テスト、実 botocore request shape 照合、workflow graph／権限／action SHA の検査、actionlint、既存 CF lint＋全テストを実行する。fixture は TEST ONLY と明記しており、runtime は cloud 応答の代わりに fixture を使わない。PHP変更はなく、task check の破壊的 style fix は今回のローカル確認対象外。production Docker build と container verify は別の実行証跡を参照する。
