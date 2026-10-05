# AWS 本番基盤 — 設計・適用・復旧・引き渡し

対象は [#673](https://github.com/kpool09122/kpool-backend/issues/673) と実行基盤補完 [#669](https://github.com/kpool09122/kpool-backend/issues/669)。東京 `ap-northeast-1` の CloudFormation 定義とオフライン検証を管理する。
[#157](https://github.com/kpool09122/kpool-backend/issues/157) が実適用、アプリ対応、秘密値、hook・疎通監視成果物、DNS、負荷・復旧・費用確認を担当し、[#156](https://github.com/kpool09122/kpool-backend/issues/156) がイメージとリリース revision、Pipeline を担当する。本変更では AWS リソースを作成していない。

## ファイルと依存順序

テンプレートと全パラメータ例は [infra/cloudformation](../../infra/cloudformation) にある。
[contracts.json](../../infra/cloudformation/contracts.json) はスタック順序、Output → Parameter の接続、外部入力、全 Outputs の機械可読契約である。基盤は PR #674 の `root.yaml` が Network / Data / Storage をネストする。**子テンプレートを別スタックとして重ねて適用しない**。bootstrap → root（package 済み）→ integration → runtime の4つだけを管理者が適用し、root/integration の実 Outputs を後続パラメータへ転記する。nestedStacks は検証用の一覧であり、deploymentOrder が実際の適用順序である。例の AWS アカウント、ARN、ID、ドメイン、メールは架空であり、そのまま適用しない。

| 順序・スタック名 | 責務 | 入力元 |
| --- | --- | --- |
| 1. `kpool-production-bootstrap` | OIDC、GitHub 配備ロール、CloudFormation 実行ロールと管理ポリシー | 認証済み管理者、repository、Environment、既存 OIDC 任意 |
| 2. `kpool-prod-base` (`root.yaml`) | #674 の Network / Data / Storage、VPC・RDS・Valkey・SQS/DLQ・S3/CloudFront | `parameters.production.json`、既存の package 用 S3 |
| 3. `kpool-production-integration` | app/migration 秘密情報の保存先、非秘密 SSM、予算 | root Outputs、通知先 |
| 4. `kpool-production-runtime` | ECR、ECS/ALB、役割別 IAM、用途別 SG/接続ルール・EC2 endpoint、hook、ログ・アラーム、Scheduler | root/integration Outputs、証明書・ドメイン、外部成果物 |

追加3スタックの ProjectName を一致させ、スタック名は `${ProjectName}-bootstrap/integration/runtime` とする。基盤は #674 の ResourcePrefix と WorkQueueName を維持し、bootstrap の FoundationStackName は root のスタック名、FoundationWorkQueueName は root の WorkQueueName、FoundationTemplateBucketName は package 用の既存 S3 名に一致させる。生成される nested stack / S3 / CacheSecret / DLQ の接頭辞に CloudFormation 管理権限を限定する。例は小文字の root 名を前提にする。例は既存 ACM ARN の経路。証明書作成は runtime の CertificateArn を空、HostedZoneId を公開 Route53 zone ID、bootstrap の CertificateHostedZoneId を同じ ID とする。ACM の DNS 検証完了を待つ。Route53 外の DNS は #157 で事前発行した東京の ACM ARN を渡す。API の DNS alias/CNAME は ALB Outputs から #157 が設定する。

## ネットワーク・データ・権限

- VPC は `10.20.0.0/16`、public は `.0.0/24` と `.1.0/24`、private は `.10.0/24` と `.11.0/24`。private route table に IGW/NAT の既定経路はない。Fargate は public subnet と `AssignPublicIp=ENABLED`、API 受信は ALB SG の API ポートだけ、worker は #674 の ApplicationSecurityGroup（受信なし）を再利用する。API と migration は runtime 専用 SG で、既存 DB/Cache SG への追加 ingress を runtime が所有する。migration は DB のみで Cache 接続は持たない。外向き通信は HTTPS と役割別 DB/Valkey ポートに限定する。private lifecycle hook は専用の EC2 interface endpoint を使って ALB ENI を発見する。SMTP 等を採用する場合は #157 で必要ポートをレビューして追加する。
- ALB 443 は公開 API の Host 条件に一致したときだけ本番ルールへ転送。8443 の検証用 listener は Hook SG だけが接続でき、既定応答はどちらも 403。API 認証・レート制御はアプリで実施し、Host 条件を認証として扱わない。
- RDS は #674 の非公開 PostgreSQL 16、`db.t4g.micro` / Single-AZ / 暗号化 gp3 20 GiB。新しい cfn-lint 1.47.0 が曖昧な `16` を W3691 と判定するため、#674 基盤への唯一の設定変更として EngineVersion を `16.13` に明示した（AWS RDS release notes 掲載・同梱スキーマ対応）。major version、論理 ID、パラメータ、保持・削除保護・バックアップは維持する。これは本番での提供状況や既存 DB の downgrade 可否を保証しない。稼働済み DB を更新する場合は実 minor が 16.13 より新しければテンプレートを現在値へ変更して Change Set を確認し、古い minor へ戻さない。適用時に東京の提供 minor version とインスタンスの組合せを確認する。minor 自動更新は有効、major 自動変更なし。DB parameter group は TLS 必須。公開 runner から DB に接続せず、migration SG の ECS 単発タスクを使う。
- Valkey Serverless は #674 の private subnet、TLS、生成パスワード認証を維持する。既存 Application SG（worker/scheduler）と API SG が 6379–6380 を使用する。root の CacheSecretArn を app execution role だけへ渡し、release の `secrets[].valueFrom` で REDIS_PASSWORD に注入する。migration execution role には許可しない。IAM 認証ユーザーや新 Cache は作らず `elasticache:Connect` は不要。DB 0、TLS と ACL/必要コマンドの互換性、Secret と ElastiCache user の同時ローテーションを #157 で実装・試験する。
- SQS は SSE、待受 20 秒、visibility 300 秒（#674 の値を維持）、保持 4 日、5 回失敗で DLQ（14 日）。worker 雛形は timeout 90 秒、停止猶予 120 秒。ジョブの冪等性・再試行・最大処理時間をこの関係に合わせる。Scheduler の配信失敗は別 DLQ、再試行 0、イベント有効期間 60 秒。起動後のタスク失敗は Scheduler DLQ に入らないため、アプリ監視で検知する。
- 公開画像も S3 自体は非公開。images bucket の CloudFront OAC だけ GetObject を許可し、配信元 Distribution ARN を固定する。画像キーは `images/*`。本人確認等は別 files bucket の `verification-documents/*`、API ロールだけが利用し、CloudFront の origin に含めない。worker は画像と受信キューだけ。migration task role に S3/SQS 権限はない。
- app execution role はアプリ用 Secret ARN の取得、migration execution role は migration 用 ARN の取得に分離。RDS 管理者 Secret は通常タスクに渡さない。既存 Secret を使う場合は同一アカウント・東京を基本とし、customer KMS key は runtime の用途別 key ARN とキーポリシーで許可する。bootstrap の hook ZIP 読取は正確な Object ARN を指定する。ZIP は同一リージョン、SSE-S3 の外部 versioned bucket を前提とする。

## 秘密情報と本番イメージの契約

#665 の [本番コンテナ実行契約](container-runtime.md) を参照。#156 のリリースタスクは、8080・`/health`、UID 1000の書き込み領域3箇所、SIGTERM・停止猶予120秒を反映する。

integration の AppSecretArn/MigrationSecretArn を空にすると **値のない保存先** を作る。#157 の管理者が秘密値を別経路で登録する。秘密値をパラメータ JSON、Outputs、Issue、シェル履歴、リリース記録へ入れない。SSM `/ProjectName/runtime/config` は非秘密メタデータだけで、完成した Laravel 設定ではない。

| 対象 | #157 / #156 へ渡す契約 |
| --- | --- |
| イメージ | ARM64、ソース・vendor・PHP 8.5/GD/MeCab を内包、起動時の依存取得なし。同一 ECR digest を API/worker/migration に使う |
| HTTP | `api` コンテナ、ApiPort（例 8080）で HTTP、HealthCheckPath（例 `/health`）が実際に 200 を返す。既存 Dockerfile の php-fpm:9000 を直接 ALB へ接続しない |
| 実行領域 | bootstrap の read-only root を維持し、Laravel storage/cache と `/tmp` に必要な書込 volume/mount を release 定義へ追加・検証 |
| アプリ Secret JSON | APP_KEY（API/worker 共通）、DB_USERNAME、DB_PASSWORD、メール/OAuth/外部 API 等の必要値。ECS `secrets[].valueFrom` に `SecretArn:JSON_KEY::` を渡す |
| migration Secret JSON | 専用 DB_USERNAME/DB_PASSWORD。管理者が DDL ユーザーと通常 DML ユーザーを作り、schema/table/sequence/default privileges を設定。RDS master で常時 migration しない |
| 非秘密 env | APP_ENV=production、APP_DEBUG=false、LOG_CHANNEL=stderr、DB_HOST/PORT/DATABASE、TLS 検証、REDIS_HOST/PORT/USERNAME と DB0、SQS QueueUrl、S3 バケット・prefix、画像 URL、API/フロント URL |
| 現状との差分 | config/queue.php は本番 Cloud Tasks、filesystems.php は local が中心。SQS/S3 設定、Valkey password/TLS/DB0、PostgreSQL `sslmode=verify-full` と CA、Cookie/OAuth/passkey を #157 で対応 |

bootstrap task definition の image は未発行 `:bootstrap-unpublished`。稼働用ではない。`describe-task-definition` で雛形を取得し、登録 API の出力専用項目を除去して、family を `*-api-release` / `*-worker-release` / `*-migration-release` / `*-scheduler-release` に変更し、digest、環境変数、Secrets、volume 等を完成させる。用途別 TaskRole/ExecutionRole、ARM64、ログ、コンテナ名を維持する。migration は `php artisan migrate --force`、scheduler は `php artisan schedule:run`。bootstrap と release の revision を共用しない。

## hook・低トラフィック監視の外部成果物

runtime は API 0 タスクで hook ZIP 未準備でも作成可能。**最初の Pipeline 起動前に** hook と canary の準備を終え、0 タスクのまま runtime を更新する。正の ApiDesiredCount は release ARN、hook bucket/key/version、ExternalCanaryAlarmName が揃わなければ CloudFormation Rules が拒否する。ECS API はこの Rules を経由しないため、#156 は起動前にこれらの Outputs とサービス設定を検証する。

### Lambda lifecycle hook

#157 は Python 3.13 / ARM64、`handler.handler` の ZIP を作成・レビューし、同一リージョンの versioned S3 に配置する。HookArtifactBucket/Key/Version と bootstrap HookArtifactObjectArn を設定する。コードのスタブや無条件成功応答は同梱しない。

- ECS `POST_TEST_TRAFFIC_SHIFT` のイベントを受信する。`hookDetails` の contractVersion=1、expectedHost、testPort=8443、healthPath と、ECS の deployment/service revision 識別子を照合する。イベント全体や認証値をログに出さない。
- private Lambda には NAT がない。internet-facing ALB の DNS が返す public IP に接続しない。EC2 private endpoint 経由の DescribeNetworkInterfaces で、VPC_ID と ALB_ARN 由来の `ELB app/<name>/<id>` description を照合し、ALB ENI の private IPv4 を取得する。複数 AZ のアドレスを試し、**API_HOST の TLS SNI・証明書検証・Host header を維持して** 8443 へ接続する。ALB_DNS_NAME だけを通常解決する実装は不可。IP 変化を前提とし固定保存しない。
- テストルール経由の新 revision の健康性、期待リリース識別子、#157 のアプリ固有 smoke test を検証する。外部 API/DB に直接出るネットワーク権限はない。API 経由で検証する。
- 全検証成功時だけ `{"hookStatus":"SUCCEEDED"}`。失敗は `FAILED`、非同期継続は ECS の契約に従う `IN_PROGRESS` と有限の再試行期限。Lambda timeout は 120 秒。例外・TLS失敗・応答不正・タイムアウトを成功へ変換しない。CodeDeploy の callback API は使わない。

### 継続 canary

ExternalCanaryAlarmName は #157 が別途提供する **既存の東京 CloudWatch alarm 名**。CloudWatch Synthetics 等の成果物・実行ロール・配置は別作業で用意する。契約は本番 API ドメインへ毎分 HTTPS/TLS とアプリ smoke test を実行し、成功/失敗を必ず計測、60 秒 × 2 回の失敗または欠損で ALARM（TreatMissingData=breaching）。ログに秘密値を含めず、通知先も設定する。canary 自身の停止も検知する。

初回は0タスクなので疎通失敗が正常。初回起動には戻し先がないことを踏まえ、後続配備を止めて正常化を確認する。2回目以降は開始前に canary が実行中かつ alarm が OK であることを確認する（既に ALARM の監視を復旧済みと扱わない）。本番へ進む前に canary の失敗注入と rollback を実証する。

ALB の 5xx Sum>=5 / 平均応答時間>=2秒は初期候補、60秒×2回、欠損 notBreaching。閾値・評価期間・欠損はパラメータ化している。無通信時は ALB 指標だけで判断できないため canary を必須契約とする。ECS 標準 BLUE_GREEN、POST_TEST_TRAFFIC_SHIFT hook、5分 bake、alarms の rollback を有効化。API は rolling 専用の DeploymentCircuitBreaker を使用せず、hook の失敗・ECS deployment 状態・alarms で判定する。正常な旧 revision がない初回や配備完了後の無期限 rollback は保証しない。worker は ROLLING と circuit breaker。

## API draining と停止の時間契約（#669）

API の FPM request 上限95秒、nginx FastCGI 無通信待ち95秒、nginx graceful shutdown 上限100秒に対し、ALB idle timeout と Blue/Green 両 target の deregistration delay は120秒、ECS StopTimeout も120秒とする。60秒 draining/既定 idle timeout では処理中リクエストを先に切断し得る。95 < 100 < 120 を維持し、処理終了・draining・SIGTERM→SIGQUIT・強制停止の各段階を区別する。idle timeout は無通信時間であって処理全体の上限ではなく、FPM 上限超過の処理を保証する設定ではない。release 定義にも StopTimeout=120 を維持する。

#157/#156 は新旧両 target で90秒程度の応答待ちを伴うリクエスト中に配備/停止し、正常応答、ALB 5xx、切断、daemon終了時刻を確認する。95秒上限を超える処理は中断される想定で、無制限処理やクライアント側の短い timeout を保証しない。worker は timeout90秒 < StopTimeout120秒 < SQS visibility300秒を維持し、重複配送・停止中の job 完了/再試行を別途確認する。

## ローカル静的検証（AWS 認証不要）

Python 3.13 と Task がある環境で実行する。導入だけ PyPI への通信が必要。cfn-lint 1.47.0 を固定し、AWS への validate-template やデプロイは呼ばない。

```sh
task cfn:install
task cfn:check
# Task がない環境でも同じ処理
bash scripts/cloudformation/run.sh check
git diff --check
```

仮想環境の既定は `/tmp/kpool-cfn-tools`。`CFN_VENV` と `CFN_PYTHON` で変更可能。検証は東京スキーマ、全パラメータ例、Outputs 接続、0タスク/無効 Scheduler、起動前提 Rules、OIDC/PassRole、非公開接続、S3 OAC、秘密値出力、保持を確認する。静的な不変条件のテストであり、AWS における権限充足・作成成功・性能・rollback 成功の証明ではない。

## 実適用手順（#157 で実行）

1. IAM Identity Center 等の短期認証で管理者がログインする。AWS account/region と既存 OIDC の有無を確認する。bootstrap の新規作成・更新は管理者本人の権限で実施し、作成した実行ロールで自分自身を更新しない。Actions に管理者資格情報を渡さない。
2. リポジトリ外の作業用ディレクトリへ各 JSON 例をコピーし、架空の値を実値へ置換する。PostgreSQL は `aws rds describe-db-engine-versions --engine postgres --region ap-northeast-1` と `describe-orderable-db-instance-options` で提供組合せを確認する。外部成果物未準備なら 0タスク・Scheduler DISABLED のまま進める。
3. root は [基盤 README](../../infra/cloudformation/README.md) に従って `aws cloudformation package` で既存の管理用 S3 に子テンプレートをアップロードし、出力された root テンプレートだけを適用する。package 用 S3 の作成・アップロードは管理者（Actions ではない）の責務で、本テンプレートは新しい artifact bucket を作らない。bootstrap → root → integration → runtime の順に Change Set を作成し、変更・置換・削除・IAM をレビューして実行する。bootstrap の Output `CloudFormationExecutionRoleArn` を `CFN_EXECUTION_ROLE_ARN` に設定し、root の Change Set 作成時には `--role-arn "$CFN_EXECUTION_ROLE_ARN"` と `--include-nested-stacks` を指定する。後続には contracts.json の接続に従って前段 Outputs を転記する。以下は単一スタックの例。新規は CREATE、既存は UPDATE を選ぶ。bootstrap に限り `--role-arn` を省略する。

```sh
export AWS_PROFILE=kpool-infrastructure
export AWS_REGION=ap-northeast-1
aws sso login --profile "$AWS_PROFILE"
aws sts get-caller-identity
# 作業用 JSON に Outputs と実入力を設定した後の例
aws cloudformation create-change-set \
  --stack-name kpool-production-runtime --change-set-name reviewed-foundation \
  --change-set-type CREATE --template-body file://infra/cloudformation/runtime.yaml \
  --parameters file:///absolute/operator-directory/runtime.json \
  --capabilities CAPABILITY_NAMED_IAM --role-arn "$CFN_EXECUTION_ROLE_ARN"
aws cloudformation wait change-set-create-complete \
  --stack-name kpool-production-runtime --change-set-name reviewed-foundation
aws cloudformation describe-change-set \
  --stack-name kpool-production-runtime --change-set-name reviewed-foundation
# 人が Change Set の内容を確認した後
aws cloudformation execute-change-set \
  --stack-name kpool-production-runtime --change-set-name reviewed-foundation
aws cloudformation wait stack-create-complete --stack-name kpool-production-runtime
aws cloudformation update-termination-protection \
  --stack-name kpool-production-runtime --enable-termination-protection
aws cloudformation describe-stacks --stack-name kpool-production-runtime \
  --query 'Stacks[0].Outputs'
```

4. 4つの親スタックで終了保護（root の子スタックは親に従う）を有効にする。SNS subscription を確認する。予算は **AWS アカウント全体** の月額 USD（既定100）、実績80%・予測100%通知。Cloudflare・外部API料金は別。ALB/public IPv4、EC2 interface endpoint 2AZ、canary、旧新同時稼働を含め #157 で再見積もりする。
5. 管理者が DB ユーザー/GRANT、Secrets、TLS、DNS、アプリ互換性を準備する。hook ZIP と canary を作成し、bootstrap の artifact permission と runtime の hook/外部 alarm 設定を更新する。Pipeline には必要 Outputs と GitHub Environment を渡す。
6. #156 がイメージを push、用途別 release revision を登録。public subnet + migration SG + public IP の単発 migration 成功を確認し、API を初回 desired=1、正常確認後に worker desired=1。初回だけ desired を変更し、通常配備は現在値を保つ。
7. 管理者が現在の release ARN・desired・ALB 対応をパラメータへ取り込み、正常な scheduler release ARN を渡して Scheduler を有効化する。DB migration を二重実行するスケジュールにしない。ジョブ重複/排他はアプリ契約で検証する。

## 通常更新と既知の drift

通常リリースは ECS API が管理し、CloudFormation は更新しない。task definition ARN、初回 desired、ALB の転送先・重みが drift する。基盤更新では次の順序を守る。

1. #156 の同じ環境への起動を運用上停止し、進行中の workflow・ECS service deployment がないことを確認する。GitHub の concurrency だけではローカル更新を排他できない。
2. `aws ecs describe-services --cluster ... --services ...` から API/worker の taskDefinition、desiredCount、loadBalancers を保存。`aws elbv2 describe-rules --rule-arns ...` で ProductionRuleArn/TestRuleArn の ForwardConfig を保存する。最新の正常リリース記録と照合する。
3. ApiTaskDefinitionArn/WorkerTaskDefinitionArn/DesiredCount を現在値にする。PrimaryTargetGroup は ECS の TargetGroupArn、ProductionTargetGroup/TestTargetGroup は各ルールで weight=1 の Blue/Green を設定する。複数宛先に非ゼロ重みがある、中間状態、サービス不安定なら更新を中断して配備を安定化する。Scheduler ARN は現在の release を維持する。
4. Change Set で旧 revision・desired=0・旧 target・data 置換への変更がないことを確認する。UsePreviousValue だけで済ませない。実行後に同じ ECS/ALB の読み取りを再度行い、運用記録を更新して配備停止を解除する。

### #156 の起動前・配備完了判定

`contracts.json.outputInventory.runtime` のキーを使用する。Outputs の存在だけを設定済みの証明にしない。条件付き LifecycleHookArn/HookInvocationRoleArn/HookExecutionRoleArn は未準備では **出力されない**。ExternalCanaryAlarmName は未準備では空文字が出力されるため、必ず非空も確認する。Parameters 例は初回0タスク専用で、配備後の更新に再利用しない。

- ECR RepositoryUri、ClusterArn、ApiServiceName/WorkerServiceName、用途別 BootstrapTaskDefinitionArn/ReleaseFamily、ContainerNames、TaskRole/ExecutionRole、SG、log group を release 定義と照合する。API 0.5 vCPU/1GiB、worker 0.25 vCPU/0.5GiB は初期候補であり負荷試験済みの容量ではない。
- `describe-services` で API controller=ECS / Strategy=BLUE_GREEN / bake=5 / Alarms Enable+Rollback / POST_TEST_TRAFFIC_SHIFT hook と、その呼出し role・対象 Lambda を確認する。API の DeploymentCircuitBreaker は不在、worker は ROLLING+breaker を確認する。通常の UpdateService で infrastructure/hook role を再設定しない。
- BlueTargetGroupArn/GreenTargetGroupArn、ProductionRuleArn/TestRuleArn、AlbInfrastructureRoleArn の対応を照合し、ALB 管理を Pipeline が代行しない。hook ZIP の bucket/key/version と bootstrap の正確な Object ARN 読取許可、Lambda code version の反映を管理者が確認する。
- ALB 5xx/latency alarm および外部 alarm の実在、東京、ActionsEnabled、通知先、監視対象を確認する。外部 alarm は毎分計測、Period=60 / EvaluationPeriods=2 / DatapointsToAlarm=2（未指定時は2）/ TreatMissingData=breaching、成功と失敗の両方が計測されることを #157 が確認する。配備開始後に alarm 名だけを渡して監視が準備済みと扱わない。
- 起動・hook 呼出しの有限の待機期限を Pipeline に設定し、ECS service deployment の失敗/rollback/タイムアウトをリリース失敗とする。`services-stable` 待機だけでは **旧 revision に rollback 済みの安定**を成功と誤認し得る。対象 deployment の成功完了、service/task の目的 release ARN/digest、production の対象、alarm状態を照合してから worker/フロントへ進む。bake 完了前に後続配備を開始しない。0タスクの安定や `/health` 200だけも成功判定にしない。
- 初回には正常な旧 API がない。hook 失敗なら本番切替を止め、初回 bake 中の異常は自動復旧先がない前提で後続を止める。2回目以降の切替前失敗は旧API維持、bake中は ECS rollback、旧API停止後は正常 release ARN/digest の手動再配備を区別する。DBの自動巻き戻しは禁止。

### 現行値の読み取り・反映・適用後照合

以下は **読取専用** の例。stack Outputs から識別子を設定し、作業 JSON と証跡をリポジトリ外に保存する。API/worker と rule を同じ停止期間に読み取り、Change Set 実行直前にも再読取して値が変わっていないことを確認する。

```sh
aws ecs describe-services --cluster "$CLUSTER_ARN" \
  --services "$API_SERVICE" "$WORKER_SERVICE" > services-before.json
# deployments の複数存在、未安定、失敗、進行中の bake/rollback を確認して中断する
aws ecs list-service-deployments --cluster "$CLUSTER_ARN" --service "$API_SERVICE_ARN" \
  --status PENDING IN_PROGRESS STOP_REQUESTED ROLLBACK_REQUESTED ROLLBACK_IN_PROGRESS
aws elbv2 describe-rules --rule-arns "$PRODUCTION_RULE_ARN" "$TEST_RULE_ARN" > rules-before.json
aws scheduler get-schedule --group-name "$SCHEDULE_GROUP" --name "$SCHEDULE_NAME" > scheduler-before.json
```

services の failures が空、desiredCount=runningCount、pendingCount=0、目的 release が稼働し、service deployment が成功完了していることを確認する（worker も同様）。現在の `loadBalancers[].targetGroupArn` を Outputs の Blue/Green ARN に照合し PrimaryTargetGroup を決める。production と test は **それぞれの実 ForwardConfig** に従い ProductionTargetGroup/TestTargetGroup を決める。各ルールの重みは片方1・他方0が本テンプレートで表現できる安定値であり、中間重み・不明なARN・TargetGroupStickiness・予定外のactionは更新を中断して個別レビューする。test の宛先を推測で反転しない。ApiTaskDefinitionArn/WorkerTaskDefinitionArn、ApiDesiredCount/WorkerDesiredCount、SchedulerTaskDefinitionArn/SchedulerState も実値から更新する。UsePreviousValue は CloudFormation の保存値を再利用するだけで ECS/ALB の drift を取得しない。

Change Set は UPDATE とし、現在値を反映した外部作業 JSON を使用する。既存データ・service/ALB/target の置換/削除、旧revision・0タスク・旧転送先への巻き戻しがあれば実行しない。適用後は `describe-services` / `describe-rules` / `get-schedule` を再実行し、task ARN・desired・primary/alternate・production/testの重み・scheduler state が読み取り時の値と一致すること、hook/alarm・目的 task の稼働を確認してから配備停止を解除する。失敗時は停止を維持して障害手順へ進む。既知driftがゼロになること自体は完了条件にしない。

ECS `UpdateService` は IAM でイメージ変更だけに制限できない。Actions の侵害時は許可されたアプリ・migration の権限でコードを実行できる。workflow の検証は独立した権限境界ではない。OIDC の aud/repository/Environment、許可ref、レビュー、ログ、バックアップを組み合わせる。通常配備から ALB/hook ロールの再設定や infrastructure PassRole を要求する実装にしない。

## 保持・障害・復旧

| リソース | 方針 |
| --- | --- |
| RDS | 削除/置換 Snapshot、終了保護、7日自動バックアップ、日次窓17:00–17:30 UTC、保守日曜18:00–19:00 UTC、DBログは #674 の自動生成を維持。ログ retention の実値と必要な保持期間は #157 で確認 |
| S3 | 削除/置換 Retain、versioning、未完 multipart 7日破棄。現行・旧versionは自動削除せず、費用と個人情報保持要件に従って別途削除 |
| Secrets/SSM/Valkey/user/group/SQS | 削除/置換 Retain。Valkey snapshot 7日、上限は #674 のサービス既定を維持し #157 で実測後に検討 |
| ECR | 削除/置換 Retain。untagged 14日、tagged release は自動削除せず、復旧対象の digest を保存 |
| CloudWatch Logs | 削除/置換 Retain、runtime 既定30日（パラメータ）。ログの秘密値マスキングはアプリ/hook 側でも実施 |
| ALB/OIDC | ALB deletion protection、OIDC provider Retain。削除は依存スタックとアカウント共有利用を確認して手動計画 |

作成・更新失敗時は `describe-stack-events` の最初の原因を確認し、秘密値をログへ転記せず権限・入力・容量・DNSを修正する。UPDATE_ROLLBACK_FAILED は原因を修復後に `continue-update-rollback`。resources-to-skip は最終手段で、対象の実状態とテンプレートを再整合する。失敗を隠すためにデータスタックを削除しない。

API の hook 失敗では旧環境を維持、bake 中の alarm で ECS rollback を確認し、後続 worker/フロントを停止する。自動 rollback もリリース失敗として記録する。配備完了後の障害は記録済みの正常 release ARN/digest を ECS へ再配備する。worker だけ失敗した場合は API の現行版を記録して worker の対象 revision を復旧する。初回失敗は戻し先がないため原因修正後の再実行。

DB は PITR/スナップショットから **別 DB** に復元し、整合性を検証後に endpoint/Secret を切り替える。アプリ rollback に合わせて DB を自動 downgrade しない。S3 は保持versionから復旧、キューは原因修正後に DLQ を確認して重複耐性を保ち再処理する。Retain された物理名は同名再作成を妨げるため、再利用は resource import 対応を確認するか新しい名前で構築し、所有関係を記録する。

## IAM のワイルドカード例外

| 対象 | 理由と制約 |
| --- | --- |
| GitHub / ECS execution の `ecr:GetAuthorizationToken`、GitHub `ecs:DescribeTaskDefinition` | リソース単位認可に非対応のため `*`。push/pull は指定 repository、登録は4つの release family、RunTask は migration family + cluster、サービス更新は2サービスだけ |
| ARN 末尾 `:*` / `/*` | release revision、task ID、stack ID、Secret suffix、ログ stream 等の実行時 ID。account/region/project/family/prefix を固定 |
| ECS ALB role の Describe API | ELB の read API が ARN 制約非対応。ModifyRule は本番/テスト2ルール、Register/DeregisterTargets は2 target group だけ |
| hook Lambda の EC2 ENI API | Lambda VPC 接続の ENI 作成/削除/アドレス管理と ALB ENI 発見に必要。専用 role、private endpoint policy は hook role の DescribeNetworkInterfaces のみ |
| CloudFormation regional 管理 | 新規 VPC/ALB/RDS/Valkey 等の未知 ID を管理する明示 API 群に `*`、RequestedRegion=東京。EC2/ELB/RDS Describe* は read inventory。IAM は列挙した runtime roles のみ。強い管理権限であり、管理者だけがこの実行ロールを指定できるようローカル認証側でも制限 |
| CloudFormation global 管理 | CloudFront/OAC 作成の未知 ID と Budgets 操作。S3 は root の生成名 prefix、Route53 は指定 zone、変更 ID の読取のみ wildcard |
| その他の管理例外 | Logs DescribeLogGroups、Secrets GetRandomPassword、RDS管理Secretの生成suffix、KMS DescribeKey の同一account/region key ID。CreateServiceLinkedRole はサービス名条件付き |

CloudFormation 実行ロールは CloudFormation service のみを信頼し、GitHub に AssumeRole、基盤変更、管理者/CF/ALB/hook role の PassRole を許可しない。既存スタックに強い実行ロールが関連付いていても GitHub は DescribeStacks だけ。管理者側でも、このロールを持つスタックの更新権限を信頼する運用者に限定する。

## #157 / #156 の実環境確認と引き渡し

全 Outputs の正確なキーは contracts.json の outputInventory を参照する。bootstrap は配備/CF/OIDC ARN、root は #674 の VPC・subnet・Application/DB/Cache SG・DB/Valkey endpoint・CacheSecret・SQS/DLQ・S3/CloudFront、integration は app/migration Secret・SSM ARN、runtime は用途別追加 SG と ECR・cluster/service・bootstrap/release family・role・ALB rule/target・log/alarm・hook・Schedulerを渡す。秘密値は別経路で登録する。

#157 では東京での create/update、IAM 実権限、証明書、ARM64起動、private DB/Valkey、S3公開分離、SMTP/外部API、Valkey password rotation、低トラフィックcanaryを確認する。失敗hook/timeoutで切替停止、bake中異常でrollback、2回目以降のBlue/Green反転、drift同期更新、worker停止とDLQ、PITR/S3復旧を実証する。API/worker増量、RDS Multi-AZ移行、Valkey上限変更は実測・費用を確認して Change Set をレビューする。

#156 は migration 成功→API native deployment完了/rollback判定→worker→フロントの順に進める。同一環境を排他し、実 task ARN、digest、両repo SHA、ALB状態、各段階結果を記録する。フロント失敗は部分成功として報告し、正常バックエンドやDBを自動で戻さない。GitHub Environment、Cloudflare Account/Worker/DNS/token と private repo checkout は #157/#156 の別契約。

## 仕様の根拠

- [ECS native BLUE_GREEN の CloudFormation 例](https://docs.aws.amazon.com/AmazonECS/latest/developerguide/migrate-codedeploy-to-ecs-bluegreen-cloudformation-template.html) と [配備失敗検知](https://docs.aws.amazon.com/AmazonECS/latest/developerguide/deployment-failure-detection.html)と [CloudFormation DeploymentConfiguration](https://docs.aws.amazon.com/AWSCloudFormation/latest/TemplateReference/aws-properties-ecs-service-deploymentconfiguration.html)：DeploymentCircuitBreaker は rolling 専用のため API BLUE_GREEN から除外し、worker ROLLING だけに設定する。API は hook・ECS deployment 状態・CloudWatch alarms で失敗を判定する。
- [ECS lifecycle hooks](https://docs.aws.amazon.com/AmazonECS/latest/developerguide/deployment-lifecycle-hooks.html)、[ALB infrastructure policy](https://docs.aws.amazon.com/aws-managed-policy/latest/reference/AmazonECSInfrastructureRolePolicyForLoadBalancers.html)、[ECS IAM resource support](https://docs.aws.amazon.com/service-authorization/latest/reference/list_amazonelasticcontainerservice.html)。
- [RDS PostgreSQL release notes](https://docs.aws.amazon.com/AmazonRDS/latest/PostgreSQLReleaseNotes/postgresql-versions.html)。静的 schema は cfn-lint の同梱版で検証し、エンジン提供状況や実サービス制約は適用前に再確認する。

## PR #674 からの追加差分と受け入れ条件の検証

- VPC/RDS/Valkey/OAC/CloudFront は各1組、S3 は2個、アプリ用 SQS/DLQ は既存2個のみ。runtime の SchedulerDlq は**ジョブ実行後の失敗ではなく ECS RunTask 配信失敗**専用の追加1個で、foundation のジョブDLQとは責務が異なる。既存 QueueArn を API/worker が参照する。
- `test_foundation.py` の10テスト（最新 #674 の画像 TLS 1.2 テスト含む）は変更せず実行する。`test_contracts.py` は root/integration → runtime 接続、重複グラフ、生成リソースの管理権限、CacheSecret と migration の分離、0タスクと activation Rules、BLUE_GREEN/5分/rollback/worker ROLLING、OIDC/PassRole、保持を検証する。
- `task infra:validate` と `task cfn:check` は全7テンプレートと全 `test_*.py` を実行する。root の W3002 除外は #674 の package 前相対パスだけに限定する。AWS 適用・秘密値登録・アプリ/TypeSpec/Pipeline/Cloudflare の変更は行わない。

最新 PR #674 (`effce134`) の画像独自ドメイン / us-east-1 ACM / TLSv1.2_2021 もそのまま使用する。root / storage の ImageDomainName と ImageCertificateArn は実適用に必須で、`parameters.production.json` の空欄を実値に置換する。`parameters/root.json` と `parameters/storage.json` は制約確認用の架空の値であり、そのまま適用しない。
