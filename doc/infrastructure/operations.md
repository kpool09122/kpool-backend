# AWS 本番基盤 — 設計・適用・復旧・引き渡し

対象は [#671](https://github.com/kpool09122/kpool-backend/issues/671) の運用手順、[#673](https://github.com/kpool09122/kpool-backend/issues/673) と [#669](https://github.com/kpool09122/kpool-backend/issues/669) の実行基盤。東京 `ap-northeast-1` の CloudFormation 定義とオフライン検証を管理する。

管理者は本書の構築・現在値同期・復旧手順、Pipeline担当者は [引き渡し台帳](pipeline-handoff.md)、実環境検証担当者は [#672検証チェックリスト](validation-checklist.md) を使用する。文書／静的検証と実適用の証跡を分離し、以下のコマンドは管理者が前提・権限を確認して実行する例である。本Issueでは実環境操作を行わない。
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
| アプリ実装と実環境の区別 | SQS は [queue契約](queue-operations.md)、S3 は [保存契約](../../docs/s3-storage.md) の実装・設定を照合する。Valkey password/TLS/DB0、PostgreSQL `sslmode=verify-full` と CA、Cookie/OAuth/passkey を含む実環境疎通は #157／#672 で確認 |

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
7. 管理者が現在の release ARN・desired・ALB 対応をパラメータへ取り込む。Scheduler は本書の追加ゲート（2用途・周期・command・監視の実装と検証）を満たしてから正常な scheduler release ARN を渡して有効化する。それまでは DISABLED を維持する。DB migration を二重実行するスケジュールにしない。ジョブ重複/排他はアプリ契約で検証する。

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

## 管理者の実行前確認・Change Set の操作票

短期認証の管理者は account／region／4親stack名／package bucketの所有者／保管場所を環境台帳で確定する。bootstrapは管理者本人のIAM権限、他3stackは指定CF実行ロールを使用する。復元・Secret登録・DNS・redriveは別途必要権限を確認する（CF実行ロールは人が直接AssumeRoleするためのロールではない）。AWS CLI v2、jq、GitHub CLIを用意し、採用したCLI versionを証跡に記録する。

```sh
export AWS_PROFILE=kpool-infrastructure AWS_REGION=ap-northeast-1
aws sso login --profile "$AWS_PROFILE"
aws sts get-caller-identity --query '{Account:Account,Arn:Arn}'
aws configure list
# 台帳のEXPECTED_ACCOUNT_IDを設定してから、別accountなら必ず中止
[ "$(aws sts get-caller-identity --query Account --output text)" = "$EXPECTED_ACCOUNT_ID" ] || exit 1
[ "$AWS_REGION" = ap-northeast-1 ] || exit 1
# 作業記録はrepo外。Secretやpayloadを含むファイルを作らない
umask 077
mkdir -p "$OPERATOR_DIR"
aws s3api head-bucket --bucket "$PACKAGE_BUCKET" --expected-bucket-owner "$EXPECTED_ACCOUNT_ID"
aws cloudformation package --template-file infra/cloudformation/root.yaml \
  --s3-bucket "$PACKAGE_BUCKET" --s3-prefix "$PACKAGE_PREFIX" \
  --output-template-file "$OPERATOR_DIR/root.packaged.yaml"
```

`OPERATOR_DIR` は管理者が指定した絶対パス、`PACKAGE_PREFIX` は今回の変更ID、package bucketは既存の東京管理用bucketである。package失敗／account違い／期限切れ認証なら適用しない。先にbootstrapの `FoundationTemplateBucketName` をこのbucketへ合わせる。rootの参照先が今回アップロードした子テンプレートであることを確認する。

各stackは `describe-stacks` で存在とstatusを確認して CREATE／UPDATE を選ぶ。存在しないというエラーとAccessDeniedを区別し、AccessDeniedを新規扱いにしない。進行中／失敗状態のstackへ重ねて更新しない。前段完了後にOutputsを次段JSONへ転記する。`contracts.json.links` の network→data は **root内部接続** であり、別親stackの操作ではない。

| 親stack | template / Parameters例 | role／特記事項 |
| --- | --- | --- |
| bootstrap | `infra/cloudformation/bootstrap.yaml` / `parameters/bootstrap.json` | role-arnなし、管理者のIAM作成権限、CAPABILITY_NAMED_IAM |
| root | package済みroot / `parameters.production.json` | bootstrapのCF role、include-nested-stacks、子Change Setも確認 |
| integration | `infra/cloudformation/integration.yaml` / `parameters/integration.json` | CF role、rootの実Outputs、BudgetAlertEmailとMonthlyBudgetUSD |
| runtime | `infra/cloudformation/runtime.yaml` / `parameters/runtime.json` | CF role、root/integrationの実Outputs、初回desired=0／SchedulerState=DISABLED |

表のParameters例は `infra/cloudformation/` 配下、実ファイルはrepo外へコピーする。既存OIDC providerがある場合は `ExistingOidcProviderArn` を設定して重複作成を避ける。各stackの操作例は前掲 `create-change-set` を用い、以下を追加確認する。

```sh
CHANGE_SET_TYPE=CREATE # create-change-setで指定した種別に合わせる。既存stackの更新はUPDATE
aws cloudformation describe-change-set --stack-name "$STACK" --change-set-name "$CHANGE_SET" \
  --query '{Status:Status,Reason:StatusReason,Changes:Changes}'
# rootではChanges内のResourceChange.ChangeSetIdを辿り子の変更もdescribeする
aws cloudformation describe-change-set --change-set-name "$NESTED_CHANGE_SET_ARN"
# 実行後、CREATEならstack-create-complete、UPDATEならstack-update-complete
case "$CHANGE_SET_TYPE" in
  CREATE) aws cloudformation wait stack-create-complete --stack-name "$STACK" || exit 1 ;;
  UPDATE) aws cloudformation wait stack-update-complete --stack-name "$STACK" || exit 1 ;;
  *) echo "未対応のChange Set種別: $CHANGE_SET_TYPE" >&2; exit 1 ;;
esac
aws cloudformation describe-stacks --stack-name "$STACK" \
  --query 'Stacks[0].{Status:StackStatus,TerminationProtection:EnableTerminationProtection,Outputs:Outputs}'
aws cloudformation list-stack-resources --stack-name "$STACK"
```

変更無しでChange SetがFAILEDになった場合はStatusReasonを確認し、変更無し以外のFAILEDを成功に読み替えない。作成・更新waitの非0／タイムアウトは完了ではない。stack eventsと実statusを再確認して障害票へ進む。`CREATE_COMPLETE`／`UPDATE_COMPLETE`、4親stackの終了保護、ALB削除保護、RDS削除保護・backup、S3 versioning、Outputsを確認して次stackへ進む。終了保護はUPDATEによる置換を防がないため、データの置換／削除はChange Setで別途中止する。

初回はhook無しでも0タスクで構築できるが、その状態は配備準備完了ではない。Secretの値、DBユーザー、東京API ACM／us-east-1画像ACM、DNS、hook version・権限、canary、アプリ接続、frontend build前のAPI URLを確認後に#156へ渡す。#156の固定SHA／digestの初回配備→migration exitCode=0→API deployment成功とbake完了→worker処理→frontend疎通→管理者の現在値同期の順を守る。

**Schedulerの有効化は追加ゲート**: [queue運用契約](queue-operations.md) は月次と動画の2用途・周期・command overrideを要求するが、現テンプレートの汎用1 scheduleはそれを実装していない。#157／#672で2用途、timezone、非0 STOPPED監視、送金再試行の制約を解消・検証するまでDISABLEDを維持する。`schedule:run` のrevisionを渡しただけで本番月次／動画が動くと扱わない。#671では不足を文書化し、無関係なtemplate変更はしない。

## 配備停止・排出・再開の具体手順

基盤変更の管理者と#156運用者は変更ID、停止開始時刻、対象Environment、全配備入口を合意する。#156が確定したworkflowファイル名を `DEPLOY_WORKFLOW` として設定する。未実装なら名前を推測しない。統合入口だけでなく本番へ直接配備できる入口すべてを対象とする。

```sh
# #156の実装後に使用。workflow無効化は新規受付停止であり、進行中の停止ではない
 gh workflow disable "$DEPLOY_WORKFLOW" --repo kpool09122/kpool-backend
 gh run list --repo kpool09122/kpool-backend --workflow "$DEPLOY_WORKFLOW" --all \
   --limit 100 --json databaseId,status,conclusion,headSha,url
 gh run view "$RUN_ID" --repo kpool09122/kpool-backend
```

queued／waiting／pending／requested／in_progressを含め未完了runを排出する。100件を超える場合はページングして全未完了runを確認する。disable前にqueuedだったrunも自動では消えない。配備中のrunを安易にcancelせず有限の完了／rollbackを待ち、タイムアウトなら実サービス状態を確認して障害対応へ。API／worker、単発migration、Scheduler更新、frontend配備、手動ローカル操作も停止期間中は競合させない。無効化できない権限・他入口が残る場合は更新しない。

前掲の現在値読み取りに加え、workerの進行中service deployment、ECS単発task、#156のリリース記録も確認する。0タスク初期構築では正常旧releaseがないため、task ARN空／未発行雛形、desired=running=0、Scheduler DISABLEDであることを確認し、初回起動手順に進む。稼働後は空ARN／雛形／desired=0を正常値として取り込まない。

| 実状態の取得先 | 反映するruntime Parameter | 判定 |
| --- | --- | --- |
| API service taskDefinition／desiredCount | `ApiTaskDefinitionArn`／`ApiDesiredCount` | 正常releaseと一致、running=desired、pending=0 |
| worker service taskDefinition／desiredCount | `WorkerTaskDefinitionArn`／`WorkerDesiredCount` | APIと異なるpartial releaseも記録して確認 |
| API loadBalancers[].targetGroupArn | `PrimaryTargetGroup` | BlueTargetGroupArn／GreenTargetGroupArnの実ARNへ対応 |
| production rule ForwardConfig | `ProductionTargetGroup` | 対応する片方weight=1、他方0 |
| test rule ForwardConfig | `TestTargetGroup` | productionから推測せず独立して読む |
| Scheduler Target.EcsParameters.TaskDefinitionArn／State | `SchedulerTaskDefinitionArn`／`SchedulerState` | scheduleのname/groupはSchedulerArnとConsoleから確定 |

例: 2回目成功後にservice primary=Green、production=Green(1)/Blue(0)、test=Blue(1)/Green(0)なら3値は `Green/Green/Blue`。ただし実testがGreenならtest=Greenとし、推測の反転をしない。10/90等の中間重み、未承認actions、未知target、進行中bake／rollbackでは更新しない。

Change Set実行直前と実行後に同じ読み取りを行い、ARN／desired／forward／Schedulerの値が保全されていること、service deploymentの対象成功、taskのdigestとalarm、hookを照合する。更新失敗、rollbackで実状態が変化、別配備の発見なら **配備停止を維持**。正常値へ再同期・復旧して管理者と#156運用者が確認してから `gh workflow enable "$DEPLOY_WORKFLOW" --repo kpool09122/kpool-backend` で対象入口を再開する。再開後の最初の配備でも現在値を再検証する。

## 障害判定と次の操作

共通: 変更／release ID、開始時刻、stack／deployment／task識別子、症状を記録し、後続とSchedulerの新規投入を止める。ログは原因に必要な範囲に限定し、Secretやqueue payload全体を貼らない。

| 状態 | 直ちに行うこと | 復旧・再開判定 |
| --- | --- | --- |
| CREATE_FAILED／ROLLBACK_COMPLETE | `describe-stack-events`、子stackも確認、権限／quota／DNS／入力修正 | 初回0タスク維持。ROLLBACK_COMPLETEは通常更新不可のため、保持データ／物理名／保護を調査し承認済み再作成またはimport計画。安易にroot削除しない |
| UPDATE_FAILED／UPDATE_ROLLBACK_IN_PROGRESS | 配備停止を維持しevents／実状態を確認 | rollback完了まで待つ。修正後、現在値読み取りからやり直す |
| UPDATE_ROLLBACK_FAILED | 失敗resourceと依存関係、権限や存在・容量を修復 | 管理者が下記continueを実施。skipは承認した最小限、直後に不整合を解消するまで再開不可 |
| API hook失敗／timeout（旧APIあり） | 旧本番targetと旧releaseを確認、worker/frontendへ進まない | artifact／app修正後、固定成果物で再配備し対象deployment成功まで確認 |
| API bake中alarm／rollback | ECSの対象deploymentと旧本番への復帰・alarmを照合 | 安定してもrelease失敗を記録。原因修正前に再配備しない |
| 配備完了・旧API停止後の障害 | 正常記録のAPI ARN／digestとDB互換性を確認 | #156のbackend復旧入口で正常revisionを手動再配備、hook→bake→alarmを確認。DBを自動downgradeしない |
| workerのみ失敗 | 新API／旧workerの実ARNをpartial releaseとして保存 | queue／serialized payload互換性を確認しworkerだけの復旧を#156で実装。対象指定が未実装なら通常backend再実行でAPI／migrationまで繰り返さない |
| 初回API／worker起動失敗 | 戻し先無し、後続停止、desired・task停止理由・hookを確認 | 修正後に初回手順を再実行、DB初期化済みか確認。desired=0に戻しただけを復旧と扱わない |
| backend成功／frontend失敗 | 新backendと旧frontendのSHA／Worker versionを記録 | 同じfrontend SHA／成果物でfrontendのみ再実行、DB／backendは戻さない |

```sh
aws cloudformation describe-stack-events --stack-name "$STACK"
# 根本原因修正後。通常はskipなし
aws cloudformation continue-update-rollback --stack-name "$STACK" --role-arn "$CFN_EXECUTION_ROLE_ARN"
# UPDATE_ROLLBACK_COMPLETE専用waiterはない。eventsとstatusを有限時間内に再読取
aws cloudformation describe-stacks --stack-name "$STACK" --query 'Stacks[0].StackStatus' --output text
```

`--resources-to-skip` はUPDATE_ROLLBACKで失敗した対象のみ。nestedの指定はAWSの形式を確認し、成功したresourceまでskipしない。skip対象はCFと実態が不一致になるため、関連Parameters／テンプレートと実状態を再整合してChange Setで確認する。bootstrapは自己CF roleを使わず管理者の権限で対応する。

## データ復元の操作票（アプリrollbackと別）

### RDS: 別DBで検証し、書込停止後に切替

#672で復元可能時刻、snapshot一覧、RPO（失われる書込範囲）／RTOを測る。管理者はroot `DatabaseIdentifier` と復元先の新識別子、private subnet group、TLS parameter group、DB SG、KMS、engine／classを確認する。**元DBを上書き／削除しない**。

```sh
aws rds describe-db-instances --db-instance-identifier "$SOURCE_DB_ID" \
  --query 'DBInstances[0].{Earliest:EarliestRestorableTime,Latest:LatestRestorableTime,Subnet:DBSubnetGroup,Groups:VpcSecurityGroups,Parameters:DBParameterGroups}'
aws rds describe-db-snapshots --db-instance-identifier "$SOURCE_DB_ID"
# いずれか一方。承認済みRESTORE_TIMEはUTCのISO8601、復元先は新ID
aws rds restore-db-instance-to-point-in-time --source-db-instance-identifier "$SOURCE_DB_ID" \
  --target-db-instance-identifier "$RESTORED_DB_ID" --restore-time "$RESTORE_TIME" \
  --db-subnet-group-name "$DB_SUBNET_GROUP" --vpc-security-group-ids "$DB_SG_ID" \
  --db-parameter-group-name "$DB_PARAMETER_GROUP" --no-publicly-accessible --deletion-protection
# またはsnapshotから
aws rds restore-db-instance-from-db-snapshot --db-instance-identifier "$RESTORED_DB_ID" \
  --db-snapshot-identifier "$SNAPSHOT_ID" --db-subnet-group-name "$DB_SUBNET_GROUP" \
  --vpc-security-group-ids "$DB_SG_ID" --db-parameter-group-name "$DB_PARAMETER_GROUP" \
  --no-publicly-accessible --deletion-protection
aws rds wait db-instance-available --db-instance-identifier "$RESTORED_DB_ID"
aws rds describe-db-instances --db-instance-identifier "$RESTORED_DB_ID"
```

復元CLIに必要な権限は管理者に付与し、#156配備ロールへ追加しない。availableだけでは合格でない。DB接続可能な検証用ECS単発タスクでverify-full、schema／migration履歴、件数・業務整合、DML／DDL権限、認証と代表APIを確認し、送金／通知など外部副作用を無効化する。復元先のDBユーザーとSecretの一致を確認する。

切替時はScheduler・producer・workerの新規書込を停止し、in-flight／queue／failed_jobsと最終書込時刻を記録する。#156が管理するrelease設定の `DB_HOST` と必要なSecret参照を復元先へ変更、用途別の新task revision／config cacheを作ってAPI／workerを入れ替え、全taskが新endpointを使うことを確認して疎通後に投入を再開する。integrationの非秘密メタデータも実endpointへ同期する。

rootのDatabaseEndpointは元のCF管理DBを参照するため、**root更新だけでは復元先へ追従しない**。復元DBのimport対応またはtemplate／接続契約の変更は#157／#672で別途設計し、元DBとの所有関係を台帳へ残す。復元先への書込開始前なら旧endpoint／Secret参照へ戻してtaskを再配備できる。書込開始後の切り戻しは差分データと外部副作用を照合・移行して承認するまで行わない（旧DBへの単純切替でデータを失わない）。

### S3: version復元と公開キャッシュ

管理者は用途別bucket、DB相対キー、復旧対象versionを照合する。`aws s3api list-object-versions --bucket "$BUCKET" --prefix "$KEY"` で**完全一致キー**のversion／delete markerを選ぶ。最新delete markerだけを除去すれば直前versionが現れるが、過去versionへ戻す場合はS3 Consoleで対象versionを同じkeyへコピーして新current versionを作る（URLエンコード・metadata・暗号化を確認）。旧versionを削除しない。

復元前後にサイズ／Content-Type／内容とDB参照を照合する。書類は認可済みAPIで確認して非公開を維持する。画像を同じURLへ戻す場合は [S3公開停止・invalidation手順](../../docs/s3-storage.md) に従い対象pathだけ失効、完了待ちとGETを確認する。新UUID keyへコピーするならアプリのDB参照も承認済み手順で更新する。秘密書類を画像bucketへ復元しない。完全消去はdelete marker追加では足りず全version／backupの保持要件を確認する。

### Valkey: セッションとロックを巻き戻さない

管理者がElastiCache ConsoleのServerless snapshot一覧で正常なsnapshot時刻／ARNを選び、新Serverless cacheへ復元する。private subnets／SG／TLS／user group／認証、利用量制限、endpointを確認し、旧cacheは保持する。原環境で試験する操作ではなく#672の承認した演習環境で行う。

古いsession、CSRF状態、認証challenge、unique job／排他lockが復活・失効し得る。復旧方針はsession失効・再ログイン、キャッシュ再生成、実行中jobと業務副作用の照合を優先し、snapshotの一括復帰だけで認証とexactly-onceを保証しない。どのkey／prefixを失効するか#157／#672が決め、全件FLUSHを安易に実行しない。`REDIS_HOST` とSecret／userを同期して新taskへ切替え、旧cacheへの書込がないことを確認する。書込後の旧cacheへの切り戻しはsession／lockの差異を評価して再承認する。CacheArn／endpointのCF再管理はRDS同様の別ゲート。

DLQ／failed_jobs／Scheduler delivery DLQは [queue復旧手順](queue-operations.md) を正本とする。原因修正、業務ID・外部副作用照合、速度制限付きredrive、再発監視を確認する。送金の外部成功／DB未保存境界が未解消なら自動再送しない。Scheduler delivery成功、ECS exitCode=0、業務job完了を別々に記録する。

## Secret更新と反映

- 管理者がSecrets Manager Console等の承認済み秘密経路で更新する。値をParameters、Outputs、ログ、コマンド引数、Issueに置かない。ARN、JSON key名、version ID、更新担当・時刻だけを記録する。
- ECSの起動時secret注入は稼働taskへ自動反映されない。#156の配備停止期間に新しいsecret参照／versionのreleaseを準備し、APIのhook／bakeを経てworker・必要な単発taskを入れ替える。config cacheは注入後に再構築し、task起動と接続成功を確認する。旧versionを参照固定したtaskが残っていないか確認する。
- APP_KEYは全API／workerで共有。暗号化データ／jobの復号と旧keyの必要期間を確認し、無計画に再生成しない。DBは新旧ユーザー／資格情報の移行とDML・DDL権限を別々に確認する。
- ValkeyはSecretだけ変えてもuserのpasswordは同期しない。新旧2 password併存→新Secret version／dynamic referenceを参照するuser更新→新taskへ切替・TLS接続確認→旧password除去の順。現data.yamlは単一passwordのversion未指定dynamic referenceなので、2 password／version指定の変更・rotation処理は#157の別実装が必要。文書の手順だけで自動rotation済みと扱わない。

## 保持・廃止の判断票

既定の保持は前掲表とテンプレートが正本。**業務承認した保存期間は未確定**であり、#157／#672が復元演習・個人情報要件・費用を踏まえて台帳へ期限とownerを登録する。バックアップがあるだけで復旧可能と判断しない。

| 対象 | 実装済み期限／扱い | 廃止前に確認すること・費用 |
| --- | --- | --- |
| RDS自動backup／snapshot | 自動7日、削除／置換Snapshot、手動snapshotの期限は未設定 | RPO、復元試験、KMS／user、法務保持。snapshot保存と旧DB稼働は継続課金 |
| Valkey snapshot | 7日、cache／user／group Retain | session／lock方針と復元試験、旧cache・snapshot利用料 |
| runtimeログ | LogRetentionDays既定30日、Retain | 障害調査・監査・PII最小化、必要部分のアクセス制限保管、ingest／保存費 |
| RDSログ | PostgreSQL／upgrade export、ログ保持期間のCF指定無し | 実LogGroup retentionをConsoleで確認し#157で承認値へ。runtime値が適用されると仮定しない |
| ECR | untagged14日、tagged release自動削除無し、Retain | 旧新task／復旧対象digestはtagで保護。SHA・digest・revision対応と復旧期限を残す。image保存費 |
| S3 | versioning、旧version自動削除無し、multipart7日 | current／旧version／delete marker／書類の完全消去、復旧可能期間と保存費 |
| package／hook artifacts | 外部bucket、CFによる期限管理無し | 使用中nested TemplateURL／hook object versionと過去復旧依存を残す。未使用だけを承認後削除 |
| Secret／SSM／SQS等Retain | stack削除で消えない、queue本文は4日／DLQ14日 | 業務データ／再処理／rotation依存、同名再作成衝突、secret／queue等課金 |

廃止は4親と子の物理resource inventoryを取り、依存／共有OIDC／DNS／稼働task／backupを確認し、承認後に終了・削除保護解除と削除計画を実施する。Retainは無料化ではない。stackを消しても残るresourceのARN・所有者・課金先を記録し、resource import可否または新名での再構築を選ぶ。ここでは削除コマンドを一括実行しない。

## 費用監視・増強・切り戻し

基準整理日: **2026-10-05**。これは料金見積りの実施日／確定金額ではない。実見積りは#157／#672がAWS Pricing Calculator等で作成し、実施日・URL／export・単価出典をアクセス制限した環境台帳へ保存する。東京、月間稼働時間、USD→JPY為替、税、無料枠／割引有無、API／worker台数、CPU／memory、RDSサイズ／Multi-AZ、Valkey使用量と上限、backup／S3旧version保存量、CloudFront転送／request数、ログ量、SQS／Scheduler回数を明示する。ALB、public IPv4、EC2 interface endpointの2AZ分、hook Lambda／canary、Blue/Greenの旧新重複とbake中タスク、移行・復元中の二重DB／cacheを含める。Cloudflareと外部APIは別請求として合算する。

integration `MonthlyBudgetUSD` と `BudgetAlertEmail` はAWS **アカウント全体** の月額COST budget、ACTUAL >80%／FORECASTED >100% のemailである。service/tag filterはない。管理者は以下とBudgets Consoleで金額・subscriber・通知条件を確認し、メール配送・通知試験の実施日と結果を記録する。forecastのデータ不足や請求更新遅延があり、リアルタイム上限・自動停止ではない。

```sh
aws budgets describe-budget --account-id "$EXPECTED_ACCOUNT_ID" --budget-name "${PROJECT_NAME}-monthly"
aws budgets describe-notifications-for-budget --account-id "$EXPECTED_ACCOUNT_ID" --budget-name "${PROJECT_NAME}-monthly"
# Consoleで各notificationのEMAIL subscriberがBudgetAlertEmailと一致することを確認
```

通知時はCost Explorerで日別／service／regionを分解し、旧新taskの残留、public IPv4、ALB／endpoint、canary、Valkey ECPU／保存量、CloudFront転送、S3 version、log量、未廃止DB／Retain resourceを確認する。Cloudflare dashboardと外部API使用量も別確認する。根拠無しのbudget引き上げや本番データ削除で収めず、ownerが原因・予測・対処・再見積りを承認する。通知未着ならsubscriber／mail filter／監視経路を修復し、#672で通知試験を再実施する。

| 増強対象 | 実測→変更経路 | 成功／切り戻し判定 |
| --- | --- | --- |
| API | CPU／memory／p95 latency／5xx／canaryと#147負荷。ApiCpu/ApiMemory/ApiDesiredCountを確認 | release利用中は雛形サイズだけ変更しても稼働revisionは変わらない。#156で承認サイズの新releaseを配備し管理者がARN／desiredを同期。bake・alarm・負荷再測定、失敗時は容量と正常旧releaseへ |
| worker | CPU／memory、queue age／depth、job時間／timeout、外部API quota | WorkerCpu/WorkerMemory、WorkerDesiredCount。新releaseと台数で反映し90<120<300を維持。二重配送／外部副作用と処理量を確認、悪化時は旧revision／台数へ |
| RDS | CPU／free memory／connections／free storage／IOPS、Single-AZのRTO | root DatabaseInstanceClass（micro/small/medium）、DatabaseMultiAZをChange Setレビュー。停止／フェイルオーバー影響、東京提供class、接続回復を確認。storage縮小／engine downgradeは切り戻し手段にしない |
| Valkey | 保存量／ECPU／latency／evictions／connections、課金 | 現templateに利用量上限Parameter無し。CacheUsageLimits追加等は#157で別変更、現値同期と再見積り。上限が性能制限／障害にならないか#672で検証、旧承認設定へ戻す |

いずれも実測→再見積り→配備停止・現在値同期→Change Setまたは#156のrelease配備→更新後照合→再開を守る。CPU/memoryはtemplate RulesとFargateの組合せを照合する。継続して閾値超過／接続失敗／queue遅延悪化なら新規投入を止め、復旧手順で戻す。性能・費用の合格値は#147／#672が実測して承認し、初期候補を試験済みとして扱わない。
