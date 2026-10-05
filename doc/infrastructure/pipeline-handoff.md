# 本番環境・Pipeline 接続台帳

操作の正本は [operations.md](operations.md)、構成・入力の正本は [インフラの入口](../../infra/cloudformation/README.md)。ここは外部接続と登録先だけを扱う。以下のGitHub登録名はPipeline担当者への提案名（アプリenvを除く）で、実装済workflowの入力名ではない。実値はアクセス制限した台帳で管理し、未確定は未登録/未検証とする。基盤入力は単一environment JSON、ARNは実Outputsから取得し重複コピーを正本にしない。

## AWS 識別子・設定先

Variables はバックエンド repository の Settings → Environments → production（名称は bootstrap `GitHubEnvironment` と一致）へ。Outputs は配備時に DescribeStacks で解決し、静的な ARN コピーを正本にしない。管理者用情報は Actions に渡さない。

| 用途 | 取得元 stack / Output または入力 | 登録先／利用先 | 確認方法・担当 |
| --- | --- | --- | --- |
| 環境・4親stack | 外部台帳の account、東京、4stack名 | Variables `AWS_ACCOUNT_ID`, `AWS_REGION`, `CFN_BOOTSTRAP_STACK`, `CFN_ROOT_STACK`, `CFN_INTEGRATION_STACK`, `CFN_RUNTIME_STACK` | STS account、DescribeStacks の region/status、基盤管理者→Pipeline担当 |
| OIDC アプリ配備 | bootstrap `DeploymentRoleArn`, `OidcProviderArn` | Variables `AWS_DEPLOY_ROLE_ARN`、AWS配備jobの OIDC | audience `sts.amazonaws.com`、subject `repo:kpool09122/kpool-backend:environment:<Environment>`、許可ref、Pipeline担当／実環境検証担当 |
| 基盤変更 | bootstrap `CloudFormationExecutionRoleArn` | 管理者のローカル `CFN_EXECUTION_ROLE_ARN` のみ | CloudFormation trust と管理者 PassRole。Actionsへの登録／AssumeRoleは禁止、基盤管理者 |
| ECR・ECS | runtime `RepositoryUri`, `RepositoryArn`, `ClusterArn`, `ApiServiceName`, `WorkerServiceName`, `ApiServiceArn`, `WorkerServiceArn` | Pipeline担当 build/publish/deploy job が Outputs から取得 | repository、cluster、2serviceを照合、digest固定、Pipeline担当 |
| 雛形・release family | runtime `ApiBootstrapTaskDefinitionArn`, `WorkerBootstrapTaskDefinitionArn`, `MigrationBootstrapTaskDefinitionArn`, `SchedulerBootstrapTaskDefinitionArn` と `ApiReleaseFamily`, `WorkerReleaseFamily`, `MigrationReleaseFamily`, `SchedulerReleaseFamily`, `ContainerNames` | Pipeline担当 の用途別release定義 | ARM64、container名、CPU/memory、volume、command、StopTimeout、Pipeline担当／実環境検証担当 |
| 実行権限 | runtime `ApiTaskRoleArn`, `WorkerTaskRoleArn`, `MigrationTaskRoleArn`, `AppExecutionRoleArn`, `MigrationExecutionRoleArn` | release定義 taskRoleArn/executionRoleArn | 用途別 ARN、Secrets/KMS、PassRole を照合、Pipeline担当 |
| 起動ネットワーク | root `PublicSubnetIds`、runtime `ApiSecurityGroupId`, `WorkerSecurityGroupId`, `MigrationSecurityGroupId` | service／RunTask の awsvpc 設定、public IP ENABLED | 用途別SG、非公開DBにVPC内migrationだけが到達、Pipeline担当／実環境検証担当 |
| API・ALB | runtime `ApiUrl`, `LoadBalancerDnsName`, `LoadBalancerHostedZoneId`, `CertificateArn`, `ProductionRuleArn`, `TestRuleArn`, `BlueTargetGroupArn`, `GreenTargetGroupArn`, `AlbInfrastructureRoleArn` | Pipeline担当 読取・起動前検証、DNSは管理者 | TLS/Host、転送先重み、ECS native BLUE_GREEN。Actions はALBを変更しない、基盤管理者／Pipeline担当 |
| hook・alarm | runtime `LifecycleHookArn`, `HookInvocationRoleArn`, `HookExecutionRoleArn`, `ServerErrorAlarmName`, `LatencyAlarmName`, `ExternalCanaryAlarmName`, `AlarmTopicArn` | Pipeline担当 起動前ゲート、管理者通知台帳 | 条件付きOutputs存在、外部名非空、コードversion、alarmと通知実動作、基盤管理者／実環境検証担当 |
| 定期処理 | runtime `SchedulerArn`, `SchedulerRoleArn`, `SchedulerDlqUrl`, `SchedulerDlqAlarmName`, `SchedulerBootstrapTaskDefinitionArn`, `SchedulerReleaseFamily` | 管理者が Target/State を管理、Pipeline担当 はrelease ARNを引き渡す | 現在の汎用1 scheduleはアプリの2周期と異なる。後述の未達を解消してから有効化、基盤管理者／実環境検証担当 |
| ログ | runtime `ApiLogGroupName`, `WorkerLogGroupName`, `MigrationLogGroupName`, `SchedulerLogGroupName`, `HookLogGroupName` | リリース記録・監視台帳 | 用途別ログ、秘密値マスキング、exitCode、Pipeline担当／実環境検証担当 |
| DB接続 | root `DatabaseEndpoint`, `DatabasePort`, `DatabaseName`, `DatabaseIdentifier` | release env `DB_HOST`, `DB_PORT`, `DB_DATABASE`、復旧台帳 | verify-full／CA、DMLとDDL権限、基盤管理者／Pipeline担当 |
| DB管理 | root `DatabaseSecretArn` | 管理者限定 Secrets Manager（通常taskへ渡さない） | DBユーザー作成／復元検証権限、基盤管理者 |
| Valkey | root `CacheEndpoint`, `CachePort`, `CacheUsername`, `CacheSecretArn`, `CacheArn` | release env `REDIS_HOST`, `REDIS_PORT`, `REDIS_USERNAME`、TLS・DB0、`REDIS_PASSWORD` はECS secret参照 | userとSecret同期、ACL、session/cache/unique lock、基盤管理者／実環境検証担当 |
| app・migration Secret | integration `AppSecretArn`, `MigrationSecretArn` | ECS secrets の JSON key参照。値は管理者のみ登録 | 値無し保存先は未準備。必要key・用途別権限と新task反映、基盤管理者／Pipeline担当 |
| 非秘密メタデータ | integration `RuntimeConfigParameterArn`, `RuntimeConfigParameterName` | SSM、Pipeline担当 がrelease設定へ変換 | 実アプリ名の非秘密envを取り込み、旧 IMAGE_BUCKET/FILE_BUCKET は使用しない。Secret/TLS URL・CA・frontend originは別注入、Pipeline担当 |
| キュー | root `QueueUrl`, `QueueArn`, `DeadLetterQueueUrl`, `DeadLetterQueueArn` | `SQS_QUEUE_URL`, `AWS_DEFAULT_REGION`、DLQ運用台帳 | 単一work queue、[queue手順](queue-operations.md)、基盤管理者／実環境検証担当 |
| 画像・書類 | root `PublicImagesBucketName`, `PrivateFilesBucketName`, `ImageBaseUrl`, `ImageDistributionId` | `AWS_PUBLIC_IMAGES_BUCKET`, `AWS_PRIVATE_FILES_BUCKET`, `IMAGE_BASE_URL`, `IMAGE_STORAGE_DISK=s3`, `VERIFICATION_DOCUMENTS_DRIVER=s3` | [S3手順](../../docs/s3-storage.md)、画像OAC／書類非公開、基盤管理者／実環境検証担当 |

秘密値の登録先は Secrets Manager または GitHub Environment Secrets。ARNや項目名の台帳でもアクセスを制限する。秘密値の取得・復元・redriveは管理者の短期認証と別途承認された権限が必要で、CloudFormation実行ロールやアプリ配備ロールで代行しない。

## Cloudflare・ドメインの対応表

| 項目 | 取得元・設定場所 | 引き渡し／登録先 | 確認・担当／未確定事項 |
| --- | --- | --- | --- |
| Account ID | Cloudflare dashboard の対象account | backend production Variables `CLOUDFLARE_ACCOUNT_ID`（提案） | token対象accountとの一致、基盤管理者→Pipeline担当 |
| Worker名・環境 | Workers & Pages、frontend側配備設定 | Variables `CLOUDFLARE_WORKER_NAME`（提案）、frontend job input | 本番Workerを特定。Wrangler等のファイル名・環境名・build/preview/deployコマンドは frontend／基盤管理者／実環境検証担当で確定し台帳へ記入 |
| frontend domain／route | Workers custom domains／routes、対象zone | frontendのWrangler等の version管理設定 | zone所有者、競合route、workers.dev利用可否、TLS、基盤管理者／frontend |
| API DNS | runtime `LoadBalancerDnsName`／`ApiUrl`、ApiDomainName | authoritative DNSのCNAMEまたはRoute53 alias | 初期はDNS-only（Cloudflare proxy無効）としてALBを確認。proxy有効化は TLS Full (strict)、cache bypass、Cookie／CSRF／Hostを実環境検証担当で試験して別判断 |
| 画像DNS | root `ImageBaseUrl`、CloudFront distributionのdomain（AWS Consoleで取得） | authoritative DNSのCNAME／alias | DNS-onlyを初期値、CloudFront OACと画像独自domainを確認。APIや書類のcacheと混同しない、基盤管理者 |
| DNS管理先 | registrarのNSとCloudflare／Route53 zone ID | 管理者環境台帳 | 権威NSを実照会し、一方にのみ設定。Cloudflare管理ならAPI証明書は事前発行しACMのDNS検証CNAMEを登録、基盤管理者 |
| 証明書 | API: 東京 `CertificateArn`、画像: root入力 `ImageCertificateArn` はus-east-1 | ACM、DNS検証レコードはDNS管理者 | ISSUED、SAN、期限、更新用CNAME維持。2regionを取り違えない、基盤管理者／実環境検証担当 |
| API URL | runtime `ApiUrl` | frontend `KPOOL_WIKI_PRIVATE_API_BASE_URL`, `KPOOL_IDENTITY_API_BASE_URL`, `KPOOL_ACCOUNT_API_BASE_URL`, `KPOOL_SITE_MANAGEMENT_API_BASE_URL` | 各frontend既存設定のpath仕様に沿って本番APIへ。build時／runtimeの使用時点をfrontendで確定、Pipeline担当／実環境検証担当 |
| bindings／secret名 | frontend配備方式の設定・dashboard | Wrangler等の bindings と Workers Secrets | **未確定**。binding名、resource ID、namespace、secret名と使用目的をfrontend／基盤管理者が一覧化。存在しないKV/R2等を前提にしない |
| 配備API token | Cloudflare My Profile → API Tokens | backend production Secret `CLOUDFLARE_API_TOKEN`（提案）、frontend配備jobだけへ明示渡し | 対象accountの Workers Scripts: Edit を基本に、採用方式の必要権限を追加レビュー。route操作時のみ対象zoneのWorkers Routes: Edit、Zone: Read等。DNS: Editや全account wildcardを無条件付与しない、基盤管理者／Pipeline担当 |
| token更新 | 管理者パスワード保管庫・失効期限台帳 | 新tokenでEnvironment Secret更新→固定成果物の配備確認→旧token失効 | 実行中jobを排出し失効順序を管理。値をCLI引数／ログ／Issueへ出さない、基盤管理者／Pipeline担当 |
| Cookie／CSRF／OAuth／passkey | backend実envとfrontend origin、OAuth provider設定 | SESSION_DOMAIN、Secure／SameSite、CSRF/CORS設定、OAuth callback、`WEBAUTHN_RP_ID`, `WEBAUTHN_ALLOWED_ORIGINS` | 親domainとAPI/frontendのsite関係、HTTPS exact origin、callback完全一致を検証。具体値は基盤管理者／実環境検証担当で確定し、認証成功・拒否の両方を記録 |

## GitHub private repository 間連携

- 本番Environment、AWS OIDC、Cloudflare token、GitHub App Secretは **バックエンド側** で管理する。初期運用は Environment required reviewers を設けず、許可refと権限で保護する（Pipeline担当契約）。frontend側Secretsが自動で利用できると扱わない。
- frontend repository の Settings → Actions → General → Access で、backendからprivate reusable workflowを呼べる範囲を限定して許可する。両repoとorganizationのActions policy、許可Actions／reusable workflowsも照合する。機能・プラン上利用できなければPipeline担当に阻害条件として戻す。
- GitHub Appを必要な2repositoryだけへinstallし、ref解決・checkoutに **Contents: read** の短命installation tokenを使用する。backend Environment Variables `SOURCE_APP_ID`、Environment Secrets `SOURCE_APP_PRIVATE_KEY` は提案名。秘密鍵は管理者保管庫にも保管し、期限・ownerを台帳に記録する。GITHUB_TOKENだけで別private repoをcheckoutできると扱わない。
- App鍵更新: 新鍵発行→Environment Secret更新→両repoのref解決／checkout試験→実行中jobがないことを確認→旧鍵失効。Contents以外のwrite権限を付与しない。token・鍵をartifactへ保存しない。
- reusable workflow参照は信頼する完全commit SHAへ固定し、アプリsourceの backend SHA／frontend SHAとは別に記録する。frontend jobにはCloudflare tokenと必要なsource取得tokenだけを明示渡しし、`secrets: inherit`でAWSやApp秘密鍵を一括共有しない。`id-token: write`はAWS操作jobだけへ。
- Pipeline担当が確定する入力・Secret名、Environment、job権限、concurrency、初回起動フラグ、復旧／再実行入力をこの表と照合してから引き渡し完了とする。現在 **Pipeline担当未実装のためworkflow実動作は未確認**。

## 未達ゲート・担当

| 未確定／文書だけでは解消できない項目 | 担当・依存 | 解消証跡 |
| --- | --- | --- |
| hook ZIP・canary・通知先、DBユーザーとSecret実値 | 基盤管理者／実環境検証担当、Pipeline担当開始前 | ARN／artifact version、alarm設定・試験、秘密値を含まないチェック結果 |
| 2用途Schedulerと非0 STOPPED監視、送金の外部成功／DB未保存境界 | 基盤管理者／実環境検証担当、[queue契約](queue-operations.md) | command／周期／timezone／監視／重複抑止試験。汎用scheduleを有効化しない |
| Workers方式、Wrangler等の場所、bindings、Next.js互換性 | frontend／基盤管理者／実環境検証担当 | 固定SHAのbuild／preview／deploy結果、binding台帳 |
| 統合workflow・Secret名・配備停止方法と排他 | Pipeline担当 | workflowファイル名／固定SHA、all／backend／frontendの試験 |
| 復元DB／CacheのCloudFormation再管理、Valkey rotation実装、保持期限の承認 | 基盤管理者／実環境検証担当 | Change Set／import可否、演習とRPO/RTO、承認済み期限 |

実値・適用・演習の記録形式は [validation-checklist.md](validation-checklist.md) を使用する。
