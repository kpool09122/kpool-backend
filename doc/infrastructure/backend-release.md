# 本番リリース Pipeline

実装入口はbackendの `.github/workflows/release.yml`。`backend` はARM64 build → ECR → migration → ECS API → worker、`frontend` はフロントrepoの固定SHAからWorkers build → 配備、`all` は両方の検証・build後にbackend → frontendを配備する。基盤作成とCloudFormation更新はローカルの管理者操作で行う。

コードの実装・ローカル検証と本番適用は別である。現時点で AWS／Workers への配備、hook の実動作、SQS canary job 処理は未確認。

## 初回設定

基盤の作成からSecret/DB設定までの順序は [構築手順](first-deployment.md)。標準hookはCloudFormation同梱で、`hook_code_sha256` は外部ZIPを使う場合に設定する。外部URLの常時監視は任意。Container Insightsは既定無効。

backendの `production` Environmentは許可タグ `v*` を使う。バック・フロントそれぞれに、マイナーバージョン単位の `release/v1.0`、`release/v1.1` などのブランチを用意する。そのブランチ上の公開対象コミットに `v1.0.0`、次の修正に `v1.0.1` のようにタグを付け、Actionsではタグを指定して配備する。タグ作成自体はブランチからの通常のGit操作で行い、配備処理は選んだタグのコミットを使う。

workflow定義は既定ブランチにも存在する必要がある。workflowの変更はレビューで保護する。production concurrencyはcancel-in-progress=false。基盤の管理者操作との排他・drift反映は [operations.md](operations.md) に従う。

## 毎回の操作

1. 両repositoryの `release/v1.0` で公開内容を確定する。同じマイナーの修正は同じブランチで準備する。
2. 公開するコミットに `v1.0.0` などのタグを付け、ブランチとタグをpushする。公開済みタグは付け替えず、次回は `v1.0.1` などを作る。
3. backendの **Actions → Production release → Run workflow** を開く。
4. **Use workflow from** でbackendのタグ `v1.0.0` を選び、`target=all`, `mode=deploy` にする。`frontend_ref` は同じタグ名なら空欄。別のタグを使う場合だけ入力する。初回のみ `initial=true`。
5. Actionsが両タグのコミットを固定し、検証・build後にbackend → frontendを配備する。結果はSummaryとartifactで確認する。

SHAの転記や互換性承認JSONの編集は不要。以下のVariablesは配備先や設定が変わるときだけ更新する。

Environment Variables:

| 名前 | 内容 |
| --- | --- |
| AWS_ACCOUNT_ID | 対象 account（12桁） |
| AWS_DEPLOY_ROLE_ARN | bootstrap DeploymentRoleArn と一致する OIDC role |
| RDS_CA_PEM | 管理者が公式 RDS 東京 truststore から取得した公開 PEM（48KB以下）。秘密情報を入れない |
| RDS_CA_SHA256 | 上記 PEM の UTF-8 bytes の SHA256。改行も一致させる |
| BACKEND_RELEASE_CONFIGURATION | 以下の非秘密 JSON。stack 名は実環境の正本から登録 |
| FRONTEND_RELEASE_CONFIGURATION | 下記のWorkers設定JSON。all/frontendで必須 |

設定 JSON の形（括弧付きの文字は実値に置換する。ARN・account の実行時既定値はない）:

```json
{
  "paused": false,
  "region": "ap-northeast-1",
  "account_id": "<account>",
  "deployment_role_arn": "<bootstrap DeploymentRoleArn>",
  "stacks": {"bootstrap": "<stack>", "root": "<stack>", "integration": "<stack>", "runtime": "<stack>"},
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
  "app_secret_keys": []
}
```

SSM integration RuntimeConfigParameterName の String JSON を読み、runtime_environment の許可キーだけを追加する。アプリの DATABASE_URL／REDIS_URL 契約を再利用する。Secret の中身を Pipeline が検証済みと扱わない。管理者が URL の TLS／CA／DB0、DBユーザー権限、Secret 登録を確認し、secret_contract を宣言する。実際の接続は migration・hook・起動時 health で確認する。

ECS は integration AppSecretArn／MigrationSecretArn の `APP_KEY`, `DATABASE_URL`, `DB_USERNAME`, `DB_PASSWORD` を JSON-key ARN 参照で注入する。API／worker は AppSecret の `REDIS_URL` と root CacheSecretArn の `password` を追加する。REDIS_URL は application user・TLS・DB0・現在の cache password と整合させる。追加アプリ Secret は許可キーを app_secret_keys に列挙する。migration へ追加アプリ Secret／cache secret は渡さない。RDS 管理ユーザーを使わない。

DATABASE_URL の query に `sslmode=verify-full&sslrootcert=/etc/ssl/certs/kpool-rds-ca.pem` を含める。公開 CA は checksum／OpenSSL 構文を検証し BuildKit mount から既存 production target に同梱する。Secret 値は build に渡さない。通常のローカル container build は CA mount 省略可能。

今回の基盤定義追加は既存 bootstrap role の scoped 読取（SSM String、project-prefix CloudWatch alarms、deployment-hook Lambda metadata、既存 work queue 属性）と runtime bootstrap task の writable volume のみ。API／worker／migration／scheduler の readonly root は維持する。runtime task 雛形と Dockerfile VOLUME により `/tmp`, `storage`, `bootstrap/cache` の所有者・mode を継承する。**管理者による既存 stack への反映が必要**。Pipeline 自身は反映しない。外部監視を任意導入する場合、ExternalCanaryAlarmName は `${ProjectName}-` prefix 内で設定する（この scoped IAM の対象）。既存 ECS revision 読取／用途別 PassRole 制約は維持する。

## 手動入力と配備

- backendのsourceと制御コードは、実行元タグの `github.workflow_sha` に固定する。frontendの指定タグはplan作成時に一度だけSHAを解決する。後からリリースブランチが進んでも同じrunのbuild・配備対象は変わらない。
- source checkout前にrepository・完全SHA・immutable planとの一致を検証し、checkout後にもHEADを照合する。mainへのマージは配備の条件にしない。
- 初回だけ `initial=true`。API と worker がともに desired=0 の場合だけ 1 にする。通常は `initial=false` で各 service の現在希望数を維持する。
- `paused=true`、unstable stack/service、未設定の配備hook/基本alarm/bake、CA未設定、異なる account／role／digest は変更前に停止する。ECR 公開前にも非 image preflight を行う。
- 検証は Taskfile の install、非破壊 cs-check、phpstan、test を使う。ARM runner で既存 production target を build し、archive SHA256 と platform を記録。公開 job は archive を検証して ECR へ公開し、ECR 応答の digest を以後の全用途へ渡す。
- migration は既存 public subnet＋専用 SG、Fargate、public IP ENABLED で実行。DB は非公開。単一 task ARN／task definition／exitCode=0 が必要。停止コード欠落・起動失敗・10分 timeout は API を止める。
- API は新 service deployment ARN を発見し、その target service revision の task definition を照合。SUCCESSFUL、5分以上の bake、target traffic weight=100、希望数／pending=0、source tasks=0、live task definition の一致が必要。旧 stable revision、rollback、wrong revision、25分 timeout は成功にならない。
- API 成功後に worker を更新。対象 revision と希望数・COMPLETED を10分以内に確認。最後に HTTPS `/health`（redirect 禁止）と SQS identity／attributes を読む。**属性読取は job 処理証明ではない**。承認済み canary の投入・完了・DLQ 確認は実環境確認に残る。

CLIからの初回実行例（両repositoryの同名タグを使用）:

```sh
gh workflow run release.yml --repo kpool09122/kpool-backend --ref v1.0.0 \
  -f target=all -f mode=deploy -F initial=true
```

通常配備は `initial=false`。ブランチやタグのpushだけでは配備は始まらない。再実行・rollbackも実行元にはリリースタグを指定し、戻すimageは `source_run` と `source_attempt` で選ぶ。

## 記録・再実行・手動復旧

artifact は run／attempt ごとに immutable な名前で保存する（90日保持、image archive は14日）。release-plan、backend-image の publication manifest、backend-record の deployment manifest／append-only event files／journal、release-result を保存。summary と record に source SHA、control SHA、image digest、task ARN、API deployment ARN、stage 結果、観測時点の API／worker revision と希望数が残る。worker 失敗でも API の成功・live revision を消さない。

SIGTERM／例外では finally で live state を照会し、観測不能も記録する。全 upload step は always 条件。runner 消失／強制停止時には最後の upload／live 照会まで保証できないので、残った immutable intent／manifest と ECS live state を管理者が照合する。AWS 応答中断後に latest SHA へ読み替えたり、自動で DB を戻したりしない。

再実行は **source_run と source_attempt（既定1）の組を指定**する。対象 artifact 名を完全一致で選び、最新 attempt を探索しない。GitHub run の backend repository／workflow_dispatch／release.yml（新規runはv付きタグ、旧main・releaseブランチrunも読取可能） 所有と記録 environment／source／release ID／digest を確認。失効 artifact、未記録 migration、実環境との不一致は fail closed。

backend-record はアップロード時の `records/` 配下と従来のルート直下の記録を読み取る。同名記録の重複・両形式の衝突は停止する。manifest は deployment manifest → backend-record の `manifest.json` → backend-image の publication manifest の順に選び、resume／rollback の run も記録元に指定できる。

| 操作 | 入力／安全上の意味 |
| --- | --- |
| 同じ digest の backend 再配備 | `mode=redeploy`, `source_run=<元run>`, `source_attempt=<元attempt>`。元 migration success が必要。build／ECR push／migration は繰り返さず、同じ digest で新 release task revision を登録し API→worker を配備 |
| API成功／worker失敗の再開 | `mode=resume-worker` と元記録。記録の API success と live API task ARN 一致を確認し worker のみ更新。migration／API更新なし |
| 手動 API 復旧 | `mode=rollback-api` と戻したい **記録済み release**。既存 task ARN／digest／role の所有を確認して同じ native BLUE_GREEN gate を通す。worker／DBを戻さない |
| 手動 worker 復旧 | `mode=rollback-worker` と選んだ release 記録。API／migrationを実行しない。現在希望数を維持 |
| GitHub の Re-run all jobs | attempt1 の固定記録から redeploy へ切り替える。digest／migration success 記録がなければ停止。検証失敗のみの回復は、記録の SHA を明示した新 deploy を選ぶ |
| migration 応答中断／成功不明 | 自動再実行しない。task ARN／exitCode／DB migration 台帳を照合。新しい deploy を明示する前に DB 担当が安全性を判断 |

replay／rollbackは記録されたbackend SHA・image digestを使い、最新ブランチへ読み替えない。frontend失敗を理由にbackend／DBを戻さない。ECR digestの保持と90日より長い記録の保管は運用側で管理する。

## backend Actionsからのfrontend配備

frontendの既存 `workers:build` / `workers:deploy` をbackendのjobから呼ぶ。公開リポジトリのref照会とcheckoutには標準の `GITHUB_TOKEN` を使う。workflow制御コードはbackendの `github.workflow_sha`、アプリはfrontendのリリースタグから解決した完全SHAに固定する。

backendの `production` Environmentへ次を設定する。

- Secret `CLOUDFLARE_API_TOKEN`。対象accountのWorkers配備・metadata読取・R2 cache投入、custom domain設定に必要な権限を与える。権限と対象account/zoneは[frontend配備手順](https://github.com/kpool09122/kpool-frontend/blob/main/docs/cloudflare-workers.md)に従う。
- Variable `FRONTEND_RELEASE_CONFIGURATION`。下記JSONを実値で設定する。`wrangler` はfrontendの `wrangler.json` を元にし、local名を本番のWorker/R2名へ変更し、account/domain/API URLを設定する。DO migration、IMAGES等のbindingを維持する。

```json
{
  "paused": false,
  "smoke_url": "https://k-pool.app/en",
  "wrangler": {
    "name": "<本番Worker名>",
    "account_id": "<Cloudflare account ID 32桁>",
    "main": ".open-next/worker.js",
    "compatibility_date": "2026-08-20",
    "compatibility_flags": ["nodejs_compat", "global_fetch_strictly_public"],
    "workers_dev": false,
    "preview_urls": false,
    "assets": {"directory": ".open-next/assets", "binding": "ASSETS"},
    "services": [{"binding": "WORKER_SELF_REFERENCE", "service": "<本番Worker名>"}],
    "r2_buckets": [{"binding": "NEXT_INC_CACHE_R2_BUCKET", "bucket_name": "<本番R2 cache bucket名>"}],
    "durable_objects": {"bindings": [{"name": "NEXT_CACHE_DO_QUEUE", "class_name": "DOQueueHandler"}]},
    "migrations": [{"tag": "v1", "new_sqlite_classes": ["DOQueueHandler"]}],
    "images": {"binding": "IMAGES"},
    "routes": [{"pattern": "k-pool.app", "custom_domain": true}],
    "vars": {
      "KPOOL_WIKI_PRIVATE_API_BASE_URL": "https://api.k-pool.app",
      "KPOOL_IDENTITY_API_BASE_URL": "https://api.k-pool.app",
      "KPOOL_ACCOUNT_API_BASE_URL": "https://api.k-pool.app",
      "KPOOL_SITE_MANAGEMENT_API_BASE_URL": "https://api.k-pool.app"
    }
  }
}
```

本番R2 bucketと対象Workerを準備し、Workerの `WIKI_VISITOR_LOCATION_SECRET` へbackendと同じ値を登録する。これはCloudflare runtime Secretとして管理し、JSON・buildには入れない。初回配備前にもWorker側のSecret名をmetadataで確認する。`smoke_url` は認証不要でredirectせず200を返すpathを選ぶ（ルートは言語redirectがあるため `/en` 等）。

| 操作 | 入力と実行順 |
| --- | --- |
| 両方を配備 | `target=all`, `mode=deploy`。実行元にbackendのタグ、frontend_refにfrontendのタグ（同名なら空欄）。両build成功後にbackend → frontend。初回のみ `initial=true` |
| frontendのみ配備 | `target=frontend`, `mode=deploy`, `frontend_ref`にリリースタグを指定（同名なら空欄）。稼働backendをそのまま使い、AWS jobは動かない |
| backendのみ配備 | 従来どおり `target=backend`。Cloudflare設定は不要 |

build jobではESLint・unit testとOpenNextのproduction buildを実行し、Cloudflare tokenを渡さない。SHA・lockfile・設定・archiveのchecksum付き `.open-next` artifactを保存する。deploy jobは同じsourceと設定を照合し、artifactを復元して再buildせず既存配備スクリプトを実行する。直前にR2 bucketとruntime Secretを確認する。frontend jobにAWS OIDC/credentialsを付与しない。

配備後はWranglerの構造化出力とCloudflareの稼働version IDを照合し、公開HTTPSの200を確認して、`frontend-record-<attempt>`へ前後のversionと結果を90日保存する。deploy失敗時も取得できた実状態を記録する。OpenNextのR2 cache投入がWorker公開より先に行われるため、失敗してもcache変更が残り得る。frontend失敗でbackend/DBを自動で戻さない。

all/frontendのGitHub Re-runやsource_runによる自動replayは停止する。失敗したrunのplanでfrontend SHAを確認し、そのコミットを指すリリースタグを指定して新しい `target=frontend` deployを起動する。backendのdigest replay/rollbackは従来どおり `target=backend` を使う。

公開したfrontend SHAとCloudflare version ID、backend SHAとECR digest・ECS revisionはActionsのartifactへ記録する。稼働版管理のためにGitHub Variablesを手で更新する操作は不要。frontend単独配備の記録には、未確認のbackend SHAを記入しない。
## ローカル確認

既存 CF tool venv と actionlint を使う。再インストールは不要。

```sh
ACTIONLINT=/tmp/kpool-release-bin/actionlint CFN_VENV=/tmp/kpool-cfn-tools task release:check
```

これは release の公開 API／state machine／AWS transport fixture テスト、実 botocore request shape 照合、workflow graph／権限／action SHA の検査、actionlint、既存 CF lint＋全テストを実行する。fixture は TEST ONLY と明記しており、runtime は cloud 応答の代わりに fixture を使わない。PHP変更はなく、task check の破壊的 style fix は今回のローカル確認対象外。production Docker build と container verify は別の実行証跡を参照する。
PR の CI infrastructure job でも同じ release／CloudFormation 検証を実行する。source 選択のテストは一時 Git repository を使い、main未マージのリリースコミットの受け入れと、checkout不一致・不正SHAの拒否を確認する。
