# 基盤の作成・更新と初回配備

管理者が本番を立ち上げ、その後も同じ入口で設定を更新するための手順。
AWS操作は `task infra:operate`、アプリ配備は GitHub Actions `Production release` を使う。
非秘密の環境JSONはリポジトリ外、Secret値はSecrets Managerで管理する。

## 初期構成

- 東京のFargate API/worker、RDS PostgreSQL 18、Valkey Serverless、ALB、S3、SQS。
- 画像はS3/OAC/CloudFront、API証明書は東京、画像証明書はus-east-1。
- ALBは30秒ごとに `/health` を確認する。5xx/遅延のalarmは通知、異常targetのalarmは通知に使う。
- 外部URLの常時監視は省略。`ExternalCanaryAlarmName` は任意。Container Insightsは既定無効で、有効化する場合だけ `inputs.runtime.ContainerInsights=enabled` を設定する。
- RDS/ECSの継続高負荷・DB空き容量、ALB異常/5xx/遅延、work/Scheduler DLQをメールへ通知する。Slackはworkspace承認後、任意のID設定で接続する。
- アプリログは既定30日。RDSの自動出力ログの保持は別途確認する。
- メールはSES API。長期AWSキーやSMTPパスワードをtaskへ追加せず、API/workerのtask roleで指定ドメインから送る。
- Wiki権限更新は毎月1日03:00 Asia/Tokyoに同期実行。送金は非公開のため定期処理を作成しない。再開時の候補は毎日03:00 Asia/Tokyo。

## 1. 環境入力と検証

`infra/cloudformation/environment.example.json` をリポジトリ外へコピーする。
account/stack/package bucket/domain/certificate/emailを実値にし、SES用に `inputs.runtime.MailDomainName` を送信ドメインに設定する。
`MonthlyBudgetUSD` は文字列のUSD金額。S3 package bucketは同一account・東京、public access block・SSE-S3・versioningを有効にする。

```sh
export AWS_PROFILE=kpool-infrastructure AWS_REGION=ap-northeast-1
ENVIRONMENT="$HOME/.config/kpool/production/environment.json"
RELEASE_CONFIG="$HOME/.config/kpool/production/release"
aws sso login --profile "$AWS_PROFILE"
aws sts get-caller-identity
task infra:validate
task infra:operate -- config-check --environment "$ENVIRONMENT"
```

`config-check` はオフライン検査。構築前に証明書のISSUED/SAN、RDS PostgreSQL18と選択instanceの東京提供、Fargate vCPU quotaを確認する。
この変更をレビューして配備対象mainへ反映した後、同じコードを基盤・配備の両方で使う。

## 2. CloudFormationの作成・更新

適用順は `bootstrap` → `root` → `integration` → `runtime`。
各親が完了してから次へ進む。既存環境の更新も同じplan/executeを使う。
稼働済み環境はリリースと単発処理を排出し、確認完了まで停止する。

```sh
STACK=bootstrap
RECORD_DIR="$HOME/.config/kpool/production/changes/$(date +%Y%m%d-%H%M%S)"
umask 077
mkdir -p "$RECORD_DIR"
task infra:operate -- plan --environment "$ENVIRONMENT" \
  --stack "$STACK" --record "$RECORD_DIR/$STACK.plan.json" --release-paused
```

表示されたChange Setを確認する。rootは子Change Setも確認する。
IAM/SG・置換/削除・費用に問題がなければ、同じ記録を適用する。

```sh
task infra:operate -- execute --record "$RECORD_DIR/$STACK.plan.json" --release-paused
```

`STACK` を次の親へ変え、新しい記録で繰り返す。runtime作成直後はAPI/workerが0、Schedulerは無効。
0台のAPI初期基盤にはヘルスチェックhookを付けない。アプリがない状態でのチェック失敗を避け、Actionsの明示的な初回起動時にhookと1台への変更を同じECS更新で設定する。稼働後はhookを必須として検証する。
配備hookはテンプレート内のPythonコードを使う。外部ZIPを使うときだけ3つのHookArtifact入力とreleaseのchecksumを指定する。
子テンプレートと大きな親テンプレートは、型を保つJSONへ変換してpackage bucketへアップロードし、object version付きURLでChange Setへ渡す。時刻などの文字列がYAMLの再解釈で数値へ変わることを防ぐ。
新規作成は成功したリソースを保持するため自動rollbackを無効にする。作成失敗時は次のplanを拒否するので、AWS eventsを確認して復旧する。既存スタックの更新は自動rollbackを維持する。

## 3. DNS・SES・通知先

runtime Outputsから次を設定する。

| 用途 | 設定 |
| --- | --- |
| API | `ApiUrl` のhostnameを `LoadBalancerDnsName` へCNAME。初期運用はCloudflareのDNSのみ |
| 画像 | root `ImageBaseUrl` のhostnameを画像distributionのdomainへCNAME。CloudflareのDNSのみ |
| SES | runtime `MailDkimName1..3` → `MailDkimValue1..3` のCNAMEをDNSのみで追加 |
| メール通知 | runtimeのSNS購読確認メールを承認。Budgetsの通知先も確認 |

次節の `configure` が生成する `dns-records.bind` は、Cloudflareの対象zoneで **DNS → Records → Import and Export** から取り込める。**Proxy imported DNS records** を外してインポートし、API・画像・SES DKIMがDNSのみになったことを確認する。[Cloudflareの取込手順](https://developers.cloudflare.com/dns/manage-dns-records/how-to/import-and-export/)

ACM検証用CNAMEは更新に必要なので残す。SES domainの検証完了後、東京SESのsandbox解除（production access）を申請する。
sandbox中は原則として検証済み宛先しか送れないため、一般利用者への認証メールを送る前に解除を確認する。
SESの送信上限、バウンス/苦情の運用、送信テストも確認する。
Slack通知は[監視設定](operations.md#slack通知と負荷アラーム)に従ってworkspaceを承認し、runtimeへworkspace/channel IDを設定する。空欄のまま構築後、同じ更新手順で後から接続してもよい。

## 4. 作成・更新共通の設定コマンド

基盤操作と同じ入口の `configure` を使う。配備するソースのSHAはActionsが解決するため、基盤設定には指定しない。

```sh
task infra:operate -- configure --environment "$ENVIRONMENT" \
  --output "$RELEASE_CONFIG" --release-paused
```

このコマンドはAWSを変更する。

- 未設定のapp/migration SecretにAPP_KEY・別々のDB資格情報・TLS接続URLを作る。既存APP_KEY/passwordは再生成しない。書込中断後の再実行も作成済みの値を維持する。
- API/workerが0の場合は、専用Fargate taskでDBユーザーと権限を作成/再適用する。管理Secretはこのtaskのexecution roleだけに許可する。
- `kpool_app` はDML、`kpool_migration` はschema/migration用。TLSはverify-full、将来作成するtable/sequenceにもアプリ権限を設定する。
- 稼働済みAPI/workerがある更新ではDB初期設定を実行せず、既存資格情報を維持する。不整合を検出した場合はrotationとして扱い、停止する。
- ローカルに `backend-release-configuration.json`、`github-variables.json`、`dns-records.json`、Cloudflare取込用の `dns-records.bind`、`rds-ca.pem` を作成/更新する。DNS取込ファイルにはDNS-only指定を付ける。既存の追加env・Secretキー一覧・pause設定は維持する。
- API配備後の再実行では、現在稼働APIのimage/env/Secret参照でWiki taskを作成/更新する。最初のbootstrap scheduleから切り替えるときに月次処理を有効化し、以後の明示的な無効化は維持する。

DB/Redisの実接続・Secret注入はAWSで確認する。任意の外部API資格情報（Google OAuth/Translation、Stripe等）は利用する機能に合わせてapp Secretへ登録し、`app_secret_keys` に実際の環境変数名を列挙する。
Wiki接続元情報の署名用 `WIKI_VISITOR_LOCATION_SECRET` はapp Secretへ生成する。frontendの同名Workers Secretにも同じ値を安全な管理経路で登録する。

## 5. GitHub設定とbackend配備

[リリース承認の設定](backend-release.md#リリース実行ごとの承認)に従って `production-approval` と `production` を用意する。`production` で承認ジョブを含むリリースタグの許可を確認し、次のVariablesを登録/更新する。

| Variable | 取得元 |
| --- | --- |
| AWS_ACCOUNT_ID / AWS_DEPLOY_ROLE_ARN | 生成した `github-variables.json` |
| BACKEND_RELEASE_CONFIGURATION | 生成した `backend-release-configuration.json` のJSON全文 |
| RDS_CA_PEM | `rds-ca.pem` の公開PEM全文 |
| RDS_CA_SHA256 | `github-variables.json` |

バック・フロントにマイナーバージョンごとの `release/v1.0` などのブランチを用意し、公開対象コミットに `v1.0.0` などのタグを付ける。Actionsの **Production release** でbackendのタグを実行元に選び、`target=all`, `mode=deploy` で起動する。frontendは同名タグなら `frontend_ref` を空欄にできる。別のタグを使う場合だけ指定する。CLI例は [リリース手順](backend-release.md#手動入力と配備) を参照する。
初回のみ `initial=true`。migration成功 → APIのBLUE_GREEN（切替後の待機なし） → workerの順に進み、配備後の公開URLへのアクセス・画面確認は運用者が行う。
失敗したstageの記録を確認し、再実行/rollbackは [backend-release.md](backend-release.md) に従う。
API/workerの正常稼働確認後、同じ `configure` を再実行してWiki scheduleを反映する。月次は初回有効化され、YouTube収集用 `${ProjectName}-video-links-collect` は実行先だけを準備してDISABLEDを維持する。YouTube APIキーとworker・収集動作を確認後、EventBridge Scheduler画面で動画収集を手動でONにする。以後の再設定と `infra:operate` 経由のCloudFormation更新は現在のON/OFFを引き継ぐ。

公開前にマスターを手動投入する。デプロイ処理は `migrate --force` のみで、Seederは自動実行しない。
ECSで現在のAPI task definitionを使う単発taskを起動し、APIと同じsubnet・security group・public IP設定で、アプリcontainerのcommandを次へ上書きする。

```json
["php", "artisan", "db:seed", "--class=Database\\Seeders\\MasterDataSeeder", "--force"]
```

終了コード0とCloudWatch Logsを確認する。API taskのapp DB資格情報・Redis設定を使い、migration専用task definitionは使わない。
ロール・ポリシーの再投入も同じ手順で実施する。[Seederの環境別動作](operations.md#マスターデータの手動投入)も参照する。

## 6. frontend配備と公開確認

frontendのbuild/deployもbackendの `Production release` から実行する。
[リリース手順](backend-release.md#backend-actionsからのfrontend配備)のCloudflare token・`FRONTEND_RELEASE_CONFIGURATION` をbackendのproduction Environmentへ設定する。

- 承認したWorker名・R2 cache bucket・account・custom domainをfrontendの配備JSONへ設定する。
- 4つのKPOOL API base URLを本番APIへ設定し、self-reference/R2/DO migration/IMAGES bindingを確認する。
- 署名用Workers Secretを登録する。本番画像hostをNextのremotePatternsで許可する。
- 個別に配備する場合は `target=frontend`, `mode=deploy` でfrontendのリリースタグを指定する。通常は手順5の `target=all` で両方を配備する。
- Cloudflare Workers等の料金はAWS見積もりと別。
- `k-pool.app` とAPI/画像のHTTPS、認証メール、ログイン、Cookie/CSRF、画像アップロード・表示、SQS実ジョブ、SNS通知を確認する。

AWS/Cloudflareへの実適用・SES承認・実メール配送・本番のcookie/queue/rollback確認は、ローカルテストで完了扱いにしない。
