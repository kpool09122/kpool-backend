# 本番インフラの運用

構成と設定の正本は [インフラの入口](../../infra/cloudformation/README.md)。本書は操作の正本。
[接続台帳](pipeline-handoff.md) は登録先・外部担当、専門文書はコンテナ/queue/S3の業務契約だけを扱う。
本書のAWS変更操作は、管理者が正しい環境・権限を確認して実行する。自動実装/CIは配備しない。

## 1. 環境入力を用意する

AWS CLI v2、Python 3.15、Taskを用意し `task cfn:install`、`task infra:validate` を実行する。Python更新時の仮想環境の作り直しは[インフラの入口](../../infra/cloudformation/README.md#検証操作の入口)を参照。
`infra/cloudformation/environment.example.json` をリポジトリ外へコピーし、account、4親stack、既存package bucket、ドメイン・ACM・通知先を実値にする。
外部入力は `inputs.<stack>.<Parameter>` に必須項目と変更する任意項目だけ記載。制約/既定値はテンプレートを見る。
Outputs・revision・desired・Blue/Green・Scheduler Stateの手入力は拒否される。

```sh
export AWS_PROFILE=kpool-infrastructure AWS_REGION=ap-northeast-1
aws sso login --profile "$AWS_PROFILE"
aws sts get-caller-identity
ENVIRONMENT=/absolute/operator/environment.json
RECORD_DIR=/absolute/operator/change-unique-id
umask 077
mkdir -p "$RECORD_DIR"
task infra:operate -- config-check --environment "$ENVIRONMENT"
```

`config-check` はオフライン入力検査のみ。ARNの実在、証明書ISSUED・SAN、quota、RDSの東京提供minor/instance組合せは管理者が確認する。
API証明書は東京、画像証明書はus-east-1。APIでRoute53自動証明書を選ぶ場合はruntime HostedZoneIdを設定する。
package bucketは同一account・東京・非公開/暗号化/versioning済みの既存bucket。ツールはowner/regionを検査する。

## 2. 初期構築と基盤変更

新規は bootstrap → root → integration → runtime。各段階の完了後に次へ進む。
既存は変更のある親だけ更新し、出力が変われば接続先の親も順に更新する。子を単独で作らない。
**既存runtimeがある場合は、計画前から検証完了まで全リリース入口・ローカル配備を停止し、queued/waiting/実行中runと単発migrationを排出する。**
進行中のbake/rollbackも完了を待つ。`--release-paused` はその確認の明示であり、分散ロックでもPipeline停止APIでもない。

```sh
# 親を1つ選ぶ。初回は bootstrap。既存runtimeが無い場合 --release-paused は不要。
STACK=bootstrap
task infra:operate -- plan --environment "$ENVIRONMENT" --stack "$STACK"   --record "$RECORD_DIR/$STACK.plan.json" --release-paused
```

plan はSTS accountと4親の存在・安定を確認し、前段Outputsをcontractsに従って自動接続する。
bootstrapだけは管理者本人の権限、他の親はbootstrap OutputのCloudFormation実行roleを使う。
runtimeのHostedZoneIdやhook bucket/key、rootのWorkQueueNameを変更すると、bootstrapの権限更新が必要になる場合がある。
planが `Bootstrap is stale` で停止したら、同じenvironmentでbootstrapを先にplan/review/executeし、対象の親を再計画する。
キュー名が既存bootstrapのProjectNameまたはFoundationStackNameの接頭辞で許可済みなら、キュー名だけのためにbootstrapを更新する必要はない。
rootはpackageし `--include-nested-stacks` で子Change Setも作る。
既存runtimeの task ARN/desired/primary/production/test/Scheduler State・ARNを実状態から取込み、旧Parametersをそのまま再利用しない。
複数deployment、中間重み、不明target、service不安定、取得失敗は中止。test targetをproductionの逆と推測しない。
計画作成中のstack/配備変化も拒否する。planは0600・上書き不可、repo外に保存する。

表示されたChange Set ARNを `aws cloudformation describe-change-set --change-set-name <ARN>` で読み、人がIAM・SG・費用・停止影響を確認する。
子の ResourceChange.ChangeSetIdも同じコマンドで確認。ツールは削除/TrueまたはConditional置換を子も含めて拒否する。
**置換・廃止はこの通常操作へ混ぜず、バックアップ・所有関係・import可否・移行/切戻し条件を別の変更計画にする。**
変更なし/FAILEDのChange Setを成功や適用済みと扱わない。作成だけで終了したChange Setは管理者が内容を確認し必要ならdelete-change-setする。

```sh
# レビュー後にだけ実行
task infra:operate -- execute --record "$RECORD_DIR/$STACK.plan.json" --release-paused
```

実行直前にaccount・stack identity・Parameters・全親と稼働状態を再取得し、planと一致しなければ実行しない。
終了保護を有効化して正確なARNのChange Setを実行し、完了待ち後にruntimeの値を再照合、結果を `.plan.result.json` に保存する。
**再確認とAWS実行は原子的ではない**。配備停止を守らない別actorや他の基盤操作があれば競合は残る。
失敗・期限切れ・状態変化・照合不一致は停止を維持し、イベントと現在状態を確認して再計画する。古いplanを手編集して再実行しない。

## 3. 通常のアプリ配備

API/worker/Schedulerとmigration等のRunTaskは root `PublicSubnetIds` と用途別SGを使用し、`assignPublicIp=ENABLED` にする。public subnetは自動public IP割当を無効にしているため、task起動設定で明示する。外向きHTTPSはInternet Gateway経由。
起動後はALB経由のAPI疎通とtaskの外向き接続を確認し、APIの8080がALB SGのみ、worker/migrationに受信許可がないことを実SGと照合する。DB/cacheの非公開設定と用途別SGも確認する。

**backend release Pipeline は `.github/workflows/release.yml` に実装済み。AWSでの動作確認は初回構築時に行う。** 固定backend/frontend SHAとimage digestを使い、migration exit=0 → APIの目的deployment成功（bake=0） → worker → frontendの順で処理する。
通常配備はCloudFormation更新なし、desiredは現在値を維持。初回0→1のみ起動時に変更する。
API BLUE_GREENは切替前hookを実行し、切替後の待機とalarmによる自動rollbackは行わない。配備後に運用者が動作確認し、必要なら手動で再配備する。5xx/遅延alarmの通知とworkerのROLLING+breakerは維持する。
`services-stable`だけでは旧revisionへのrollbackを成功と誤認するため、目的revision/digest・deployment結果・転送先も確認する。
詳細な引き渡し項目は接続台帳、API8080/health・read-only volumes・ARM64・signalは[コンテナ契約](container-runtime.md)。

必要なリソース/Secret/配備時hookが未準備なら起動しない。hookはprivate接続でALB ENIを発見し、API Host/SNI・TLS検証を保って8443の新revisionを検査する。
外部URLの常時監視は初期運用では省略する。`ExternalCanaryAlarmName` を設定した場合だけ、外部アラームを配備前検査とrollbackへ含める。配備時hookは標準コードをテンプレートに同梱し、VPC内からALBの8443へHost/SNIを保持して `/health` を確認する。外部ZIPを使う場合は承認したchecksumをrelease設定へ登録する。
Wiki定期処理は毎月1日03:00 Asia/Tokyo、`wiki:process-role-promotion --sync`。初回はDISABLEDで作成し、API配備後に同じ `infra:operate configure` を実行すると現在のAPI image/env/Secret参照でtask revisionを登録して有効化する。以後は明示的な無効化を維持し、現在imageへ更新する。送金バッチは初回公開で作成・有効化しない。再開時の候補は毎日03:00 Asia/Tokyo。Scheduler DLQは起動要求の配送失敗、EventBridgeのSTOPPED通知はtask起動失敗・非0終了を扱う。

YouTube収集は同じSchedule Group内の `${ProjectName}-video-links-collect` が30分ごとに `video-links:collect` を起動し、SQSへ収集Jobを登録する。初回作成時はDISABLED。API配備後の `infra:operate configure` で現在のAPI image/env/Secret参照を持つscheduler release taskへ切り替えるが、動画収集のON/OFFは変更しない。YouTube APIキーの注入とworkerの稼働を確認した後、本番では運用者がEventBridge Scheduler画面で手動有効化する。

インフラの再適用は `infra:operate plan` → Change Set確認 → `infra:operate execute` を使う。この経路は月次・動画それぞれの現在のStateとtask revisionを取得し、CloudFormationパラメータへ引き継ぐ。計画後にON/OFFを変更した場合は再計画する。CloudFormationコンソール等から直接更新する場合、この状態取り込み処理は動かないため、`VideoCollectionSchedulerState` と `VideoCollectionTaskDefinitionArn` に現在値を設定する。`configure` の再実行でも手動で有効化・無効化した状態を保持する。

### マスターデータの手動投入

Seederはすべて手動実行する。通常デプロイはマイグレーションのみ自動実行し、Seederは呼ばない。
マスターのみ投入する場合は、アプリcontainerで次を実行する。本番では[初回配備手順](first-deployment.md)のECS単発taskを使う。

```sh
php artisan db:seed --class='Database\Seeders\MasterDataSeeder' --force
```

`MasterDataSeeder` はWiki・Account・SiteManagementのロール/ポリシーと、既存アカウントの権限設定を整える。
システムロール/ポリシーは既存IDを維持し、定義内容を更新するため、再実行できる。

| 実行対象 | local / testing | production / staging / その他 |
| --- | --- | --- |
| `MasterDataSeeder` | マスターのみ | マスターのみ |
| `DatabaseSeeder`（`db:seed`の既定） | マスターとサンプル | マスターのみ |
| `TestAccountSeeder` / `WikiEditorSampleSeeder` を直接指定 | サンプルを投入 | 何も投入しない |

`--force` は本番確認プロンプトの省略であり、サンプル投入を許可する設定ではない。

### Slack通知と負荷アラーム

CloudWatchアラーム → runtimeの `AlarmTopic` → Amazon Q Developer in chat applications → Slackで通知する。既存メール通知も維持する。Slack連携設定・通知role・各アラームはCloudFormationで管理する。

| 対象 | 初期設定 |
| --- | --- |
| RDS CPU | 平均80%以上が5分継続 |
| RDS空きストレージ | 3GiB未満が5分継続（初期20GBに対する値。容量変更時に見直す） |
| API / worker CPU | 各serviceの平均80%以上が5分継続 |
| API / workerメモリ | 各serviceの平均85%以上が5分継続 |
| ALB異常target | blue/greenのいずれかで異常targetが1台以上、2分継続 |
| API 5xx / 応答遅延 | 1分のtarget 5xxが5件以上 / 平均応答2秒以上、それぞれ2分継続。配備rollbackにも使う |
| work / Scheduler DLQ | 取得可能なmessageが1件以上 |

すべてのCloudWatchアラームで異常とOKへの遷移を同じSNSへ通知する。初回作成時のOK遷移でも通知が出ることがある。欠損は異常扱いにしないため、負荷アラームは停止そのものの検知ではない。標準RDS/ECS/ALB/SQS metricsを使い、Container InsightsやDatabase Insightsの有効化は不要。CloudWatchアラームとSNSの費用は見積もりへ反映する。

Slackの接続手順:

1. 管理者が[Amazon Q Developer in chat applications](https://console.aws.amazon.com/chatbot/)でSlack workspaceを承認する。[AWS公式手順](https://docs.aws.amazon.com/chatbot/latest/adminguide/slack-setup.html)に従って対象channelへアプリを招待する。
2. 承認したworkspace IDと通知先channel IDをenvironment JSONの `inputs.runtime.SlackWorkspaceId` / `SlackChannelId` に設定する。新規構築時に両方空ならSlack資源を作成せず、既存メール経路を使う。片方だけの設定は拒否する。
3. 今回追加した権限を先に反映するため `bootstrap`、次に `runtime` を通常のplan/review/executeで更新する。以後のchannel IDや閾値変更はruntime更新で行う。workspaceの変更や、作成済みSlack資源の削除は通常更新とは別に計画する。
4. runtime Output `SlackNotificationConfigurationArn` を確認し、Amazon Qの「Send test message」で受信確認する。実アラームのALARM/OK通知も確認する。

```json
{
  "SlackWorkspaceId": "TXXXXXXXX",
  "SlackChannelId": "CXXXXXXXX",
  "LoadAlarmEvaluationPeriods": "5",
  "CpuAlarmThresholdPercent": "80",
  "MemoryAlarmThresholdPercent": "85",
  "DatabaseFreeStorageAlarmThresholdBytes": "3221225472"
}
```

上記は `inputs.runtime` 内の設定例。IDは実値に置き換える。CPU/メモリ閾値は負荷実測を見て調整する。ALBの既存閾値は `AlarmPeriod` / `AlarmEvaluationPeriods` / `ErrorCountThreshold` / `LatencyThresholdSeconds` で変更する。

通知roleはCloudWatchのアラーム・metric読取に限定する。guardrailもCloudWatch読取に制限し、AWSの変更・Secret取得・Lambda実行は許可しない。チャット連携のログ出力は `NONE`。定期処理のSTOPPEDイベントからSNSへ送る既存のテキスト通知はメール向けで、このCloudWatchアラームのSlack経路とはメッセージ形式が異なる。

## 4. 設定・Secret・容量を変える

- 非秘密設定: environmentの該当inputsを変更し、関連親をplan/review/execute。integration SSMは実アプリ名の非秘密envを出力する。PipelineがSSMを読みECS envへ変換する。
- Secret: appとmigrationの用途別ARNに必要JSON keyを保管庫/管理端末の安全なファイル経路で登録。CLI引数/ログ/履歴へ値を出さない。新task起動で注入を更新し、新旧taskの接続を確認して旧資格情報を失効。本番imageはconfig cacheを構築しない（[コンテナ契約](container-runtime.md#環境変数秘密情報)）。
- APP_KEYはAPI/worker共通。安易な変更はCookie/session/暗号化済データを壊す。通常の資格情報rotationと分ける。
- PostgreSQLはDML/DDLを分離。既存configのsslmode既定preferに依存せず、DATABASE_URLのqueryでsslmode=verify-full/sslrootcertを渡し、信頼するCAをimageへ同梱。検証済URLのSecret注入・CA配布はrelease側の責任。
- ValkeyはTLS URL（REDIS_URL）、applicationユーザー・password、DB0を設定。Secret値だけの更新ではcache userは同期しない。新旧2password期間、user/passwordとSecret更新、新task反映、旧password除去の順で移行する。自動rotationは未実装。
- API/workerの容量: runtime ApiCpu/ApiMemory等を変更し、planが保持するrevision/desiredを確認。release定義のCPU/memoryもPipeline側で更新しなければ既存revisionへは反映されない。
- DB instance/Multi-AZ: root inputsを変更し、再起動/費用/提供versionを確認。自動minor upgrade後の実versionより古いEngineVersionを適用しない。
- PostgreSQLの新規作成はメジャー18 / parameter group `postgres18` を使用し、RDSが選択したminorをDB情報で確認する。東京でのengine versionとinstance classの提供を確認する。既存DBが16等の場合は通常更新と分けて、復元環境でアプリ/拡張機能の互換性、対応upgrade経路、backup、停止時間、復旧条件を検証する。メジャー更新時は対応するparameter groupの変更と `AllowMajorVersionUpgrade` の明示的な有効化が必要で、通常テンプレートでは `false` を維持する。parameter groupの置換を含む計画は§2の専用移行計画として扱う。
- Budgetsはaccount全体のUSD（雛形200、テンプレート既定100、実績80%/予測100%）。Cloudflare/外部APIは別。旧新task併存、public IPv4、ALB、private endpoint、canary、保存version・Retain課金も見積もる。

## 5. 障害・復旧・切戻し

1. 配備/基盤変更を停止し、目的revision・実task/target・変更ID・最初のstack eventを非秘密の記録へ保存。ログ全文/queue payload/認証値を記録しない。
2. hook失敗は旧APIを維持し、リリース失敗として扱う。切替後のalarmによる自動rollbackは行わない。完了後の障害は記録済正常revision/digestへ対象サービスだけ再配備する。worker失敗やfrontend部分成功で正常backend/DBを自動で戻さない。初回は正常な旧APIが無いため修正後再実行。
3. CloudFormation UPDATE_ROLLBACK_FAILEDは原因修復後continue-update-rollback。resources-to-skipは最後の手段で、実状態/テンプレートの再整合が必要。データstack削除で隠さない。
4. RDSはPITR/snapshotから**別DB**へ復元し、TLS・権限・schema・業務整合を確認。書込停止後endpoint/Secretを切替。旧DBを保持し、新DBへ書込み後の切戻しはデータ再整合を伴う。アプリrollbackでDBをdowngradeしない。
5. Valkeyは別cacheへ復元し、user/Secret/TLS・session再ログイン・challenge/lockを確認。復元したlockを副作用の成功証拠にしない。外部決済等は業務照合が必要。
6. 復元DB/cacheは既存Outputsへ自動追従しない。通常の接続自動取込みを再開する前に、対応リソースのCloudFormation import可否と新名前による再管理を確認する。未対応のimportを保証しない。再管理前に通常planで旧endpointへ戻さない。
7. S3はversion/delete markerとDBキーを照合し復旧、画像は必要なinvalidation完了後確認、書類は認可APIだけ。[S3手順](../../docs/s3-storage.md)。DLQ/failed_jobsは副作用と重複を照合して速度制限で再処理。[queue手順](queue-operations.md)。
8. 復旧後の接続・目的revision・転送先・alarm・業務動作を確認して停止を解除する。RPO/RTOと残留課金を記録する。

## 権限・監視の判断

管理者本人→CloudFormation service role、Actions→用途限定ECR/ECS/PassRole、task→用途別S3/SQS/Secretの境界を維持。
ワイルドカードには未知IDを作るAPIやresource指定不可のread-only APIが含まれるが、CloudFront/Budgetsの変更権限にも残る。すべてをAPI制約だけで正当化せず、管理者権限の残余リスクとして[設計判断](design-decisions.md)を確認する。
ARNのaccount/region/project/prefix・RequestedRegion・PassedToService・OIDC aud/Environmentを組み合わせる。
Actionsに基盤変更・管理role PassRole・Secret値取得は与えない。ただしECS UpdateServiceはimage変更だけにIAM制限できず、侵害時はアプリ/DDL権限のコード実行が残る。用途別DB/role・監査・backupで影響を抑える。

ALB5xx/latency/異常target、ECS失敗、Scheduler DLQとtask失敗通知、SNS購読と配送を確認する。外部canaryは導入した環境のみ確認する。Scheduler配信成功はtask/job成功ではない。
本変更の権限/保護維持・テスト削除の理由とAWS未検証事項は[設計判断](design-decisions.md)、実施結果は[検証票](validation-checklist.md)。
