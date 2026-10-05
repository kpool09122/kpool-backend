# SQS / EventBridge 実行・復旧契約（#666）

関連: [AWS配備手順](operations.md)、[CloudFormation](../../infra/cloudformation/README.md)。
この文書はアプリ側の契約であり AWS の作成・有効化を行わない。本番イメージとローカルの起動/停止検証は[コンテナ契約](container-runtime.md)に実装済み。実AWSの疎通・停止・監視確認は別ゲートである。

## Producer と配送先

| producer | 論理キュー | Redis（local/testing） | SQS（production/staging/preview） | job 方針 |
|---|---|---|---|---|
| パスキー復旧コード/完了、SSO linking の queued mail | default | default | `SQS_QUEUE_URL` | worker tries=3 |
| CollectVideoLinksJob | default | default | 同上 | tries=1、uniqueFor=2700秒 |
| ProcessRolePromotionJob | default | default | 同上 | tries=1、対象月 unique、uniqueFor=3600秒 |
| SyncPayoutAccountJob | webhook | webhook | 同上 | tries=3、backoff=60秒 |
| ExecuteTransferJob | settlement | settlement | 同上 | tries=3、backoff=60秒 |

その他の招待・問い合わせ・権限通知などの同期 `Mail::send` は従来どおり同期送信である。
#673 は **単一 standard work queue** を作成している。物理名は root `WorkQueueName`（本番例 `kpool-prod-work-v1`）、URL は integration の `QueueUrl` を実行環境へ注入する。`config/queue.php` の routing は SQS 時のみ webhook/settlement をこの URL に対応させる。専用キューを新設しない。SQS 上では優先順・FIFO は保証しない。

`QUEUE_CONNECTION` は明示指定を優先する。Redis へ上書きすると論理名を維持する。SQS を利用する環境は `AWS_DEFAULT_REGION=ap-northeast-1` と QueueUrl を設定し、API と worker を同じ設定の新taskへ入れ替える。本番imageはconfig cacheを構築しない。長期 AWS キーは設定しない。SDK は ECS task role credential chain を使用する。単独で per-job `onConnection` を差し替える運用はせず producer/worker の既定 connection を揃える。
`SQS_PREFIX/SQS_QUEUE/SQS_SUFFIX` による名前解決も可能だが、#673 との連携は完全 URL を正とする。

API/単発 producer に必要なのは `sqs:SendMessage,GetQueueAttributes,GetQueueUrl`、worker に必要なのは `ReceiveMessage,DeleteMessage,ChangeMessageVisibility,GetQueueAttributes,GetQueueUrl` を QueueArn のみに限定した権限。runtime の API/worker role はこの分離を持つが、**現在のScheduler雛形は受信専用WorkerTaskRoleを使い、producerのSendMessage権限を持たない**。用途別producer roleとPassRoleを設計・検証するまで月次/動画のenqueueに使わない。worker が将来 nested job を dispatch する場合も SendMessage を明示的に検討する。operator の DLQ 再投入権限はアプリ role に付与しない。

Cloud Tasks connection、認証 HTTP handler、専用 middleware/config と driver 依存を撤去。Google Translation/OAuth は残るので `google/cloud-translate` / `google/auth` と runtime ADC は維持する。`after_commit=false` は従来方針を継承し、トランザクション外への公開タイミングを一律変更しない。

## Worker / 停止 / 再試行

本番コマンドは #673 WorkerBootstrapTaskDefinition と同一:

```sh
php artisan queue:work sqs --timeout=90 --tries=3
```

完全 URL の既定 work queue のみを consume する。`--queue=webhook,settlement,default` を SQS に付けると未作成の物理名へアクセスするため禁止。
local は `task queue-work`（`webhook,settlement,default` の優先順、timeout=90、tries=3）。`task queue-work-sqs` は注入済みの設定を使う開発コンテナ用で、本番イメージの代替ではない。

| 値 | 契約 |
|---|---|
| worker timeout | 90秒、PCNTL が必要 |
| ECS StopTimeout | 120秒（#673）、SIGTERM の伝播は #665 |
| SQS visibility | 300秒（storage.yaml） |
| SQS maxReceiveCount | 5（storage.yaml）、DLQ保持14日 |
| Redis retry_after / block_for | 300秒 / 5秒 |
| ジョブ tries / backoff | ジョブ指定を優先。上表の値を保持。mail は worker fallback |

90 < 120 < 300 を維持する。SIGTERM で worker は現在の job を終えて停止する。90秒で終了しない仕事は worker timeout により失敗・再配送し得る。外部 HTTP/Stripe/YouTube に処理全体の上限が保証されているわけではないため #157 で実測し、90秒内に収まらなければ Scheduler を有効化せず処理分割/外部 timeout を別途設計する。visibility 延長を暗黙には期待しない。
ローリング更新は producer/worker の serialized class とコード、`APP_KEY` を互換にする。encrypted job の復号も含め同じ APP_KEY（ローテーション時 APP_PREVIOUS_KEYS）を共有する。worker は長寿命なので deploy 後に入れ替える。`queue:restart` の通知には共有 Redis cache が必要。
ShouldBeUnique は **dispatch 時の共有 cache lock** であり永続冪等性ではない。API/worker/単発タスクは同じ Redis cache store/prefix を使う。終了後・期限後の再配送は別途業務側で安全性を確認する。

## 失敗保存と復旧（別々に扱う）

1. Laravel が tries 到達/例外により失敗確定すると failed_jobs（database-uuids）へ記録し、SQS メッセージを DeleteMessage する。全失敗が SQS DLQ へ行くわけではない。既存 failed_jobs migration を先に適用する。
2. `php artisan queue:failed` で UUID/connection/queue/例外を確認、原因修正・副作用照合後に `php artisan queue:retry <uuid>`。一括 retry は送金確認なしに実行しない。`queue:forget <uuid>` / `queue:flush` は監査後のみ。
3. SQS DLQ は worker が停止/強制終了し削除できないなどで受信回数5を超えたメッセージ。CloudWatch ApproximateNumberOfMessagesVisible / age と DLQ alarm を監視する。operator が AWS Console で DLQ/receipt/payload/元 queue を確認し、原因修正後に元 work queue へ StartMessageMoveTask (redrive) を速度制限付きで実施する。必要権限は DLQ の ReceiveMessage/DeleteMessage/GetQueueAttributes/StartMessageMoveTask と元 queue の SendMessage（暗号鍵を変更した場合 KMS権限も）。このPRでは実行しない。
4. Queue::retry と SQS redrive を同じ job に二重適用しない。ログ/業務IDと受信回数、failed_jobs、SQS DLQ を照合する。

送金は保存済み SENT の再配送をスキップする。**Stripe 成功後・DB 保存前に kill された場合は未解決**: 現行 Stripe client に業務ID由来の idempotency key はなく、並行処理/外部成功後の再試行で二重送金を保証できない。#157 で settlement を運用開始する前にこの境界を解消する必要がある。送金は現行UseCaseで gateway エラーを FAILED として保存して正常 return するため、queue failure だけでなく TransferStatus/Stripe結果も監視する。
支払先同期は同じ externalAccountId の更新/削除、動画は収集ステータスと既存URL、月次は現在ロール/集計状態を利用するが、通知/外部副作用まで exactly-once を保証しない。途中停止後の再実行は業務状態を照合し、専用ロック/Lua を追加しない。

## EventBridge → 公開 CLI

| コマンド | 引数・対象 | 0 の意味 | Scheduler 契約 |
|---|---|---|---|
| `wiki:process-role-promotion` | `--month=YYYY-MM`（01〜12）、省略時 Asia/Tokyo の現在月。`--sync`可 | 通常: enqueue 成功（unique 抑止時は新規送信しない）。sync: 集計と昇降格完了 | `cron(0 3 1 * ? *)`、timezone Asia/Tokyo |
| `video-links:collect` | 引数なし、既存 Job を dispatch | enqueue 成功（unique 抑止可）、YouTube未設定時は job が skip | `rate(45 minutes)` |
| `settlement:process-due-transfers` | `--date=YYYY-MM-DD`、省略時 Asia/Tokyo の今日00:00、`--dry-run`可 | 全対象 enqueue、対象なし、dry-run | 頻度は未合意。#157 が決定、勝手に追加しない |

不正引数・依存障害・enqueue 失敗・sync 失敗は非0（1）。複数送金の途中 enqueue 失敗は既送信分を取り消さない。通常の command 成功は業務完了ではない。
production の Laravel Schedule は月次を登録しない。local/testing/staging/preview は公開月次コマンド経由で毎月1日03:00 JST。非productionで外部Schedulerを使う場合は Laravel schedule:run と併用しない。
#673 の現在の Scheduler は bootstrap の汎用枠（既定 rate(1 minute)、DISABLED）であり、実配備時に #157/#673 が上記 **月次と動画の2つの定義**、適切な command override、両方 **DISABLED**、FlexibleTimeWindow=OFF を設定する。既定の汎用式をアプリ周期とみなさない。#157 の動作確認後だけ ENABLED。送金を追加する場合も初期DISABLED。

確認は3段階:
- Scheduler: TargetErrorCount/InvocationDroppedCount と Scheduler delivery DLQ（RunTask API/IAM/容量失敗）。Scheduler の retry/DLQ はアプリ work queue DLQ と別。
- ECS: RunTask が受理されても container success ではない。task ARN を記録し STOPPED event の container exitCode/stoppedReason、CloudWatch Logs を監視。非0 exit alarm/通知を #673/#157 で実装する。単なる Scheduler 成功から再実行しない。
- Job: worker log/failed_jobs/Transfer状態と DLQ を業務IDで確認。CLI 0 だけで完了と判定しない。

## Cloud Tasks から切替・ロールバック

1. #665/#673/#156 のイメージ・runtime env・配備を揃える。旧 producer を止め、旧Cloud Tasksのqueueを一覧し未処理/遅延/実行中タスク数と失敗を記録する。
2. 未処理があれば **旧コードと認証 handler を残した旧環境で drain**。この版は旧 handler を持たないので先に新コードへ差し替えない。drain不能なら業務ID/送信メールを照合して手動移送、再送と重複の承認を得る。Cloud Tasks payload を直接 SQS へコピーしない。
3. APP_KEY/serialized code/config/共有 cache、failed_jobs migration、QueueUrl/IAM を一致させる。新 worker を起動、fixture mail/default/webhook/settlement を確認後に新 producer へ切替。旧queueが空で in-flight なしを確認して旧 handler/権限/設定を廃止する。
4. rollback は producer停止 → SQSのvisible/in-flight/DLQとfailed_jobsを記録 → 互換workerでdrainまたは照合 → 旧アーティファクトと設定へ戻す。新コードの `QUEUE_CONNECTION=cloudtasks` だけで復旧はできない。両 producer の同時稼働を避ける。

## 実環境検証の引渡し

- 本番イメージ: API / worker / 単発コマンド、PID1/PCNTL、signal/exitのローカル検証は `task container:verify`。ECS上のSIGTERM→現在job完了→120秒内STOPPEDは実AWSで別途確認する。ローカルverifyはRedisを使い、SQS成功を証明しない。
- #673/#157: work queue=QueueUrl、visibility=300、maxReceiveCount=5、StopTimeout=120、timeout=90/tries=3。Schedulerの2周期・初期DISABLED、delivery DLQと非0STOPPED検知は別設定として確認する。
- #157: real task roleで送信→受信→正常削除、例外→60秒release、tries超過→failed_jobs/delete、worker kill→visibility後再配送→DLQ、ローリング停止とqueue:restart。SENT再実行と外部成功/DB未保存境界を照合する。
- #157: mail delivery、Google Translation/OAuth疎通、YouTube設定/skip、月次sync/async、dry-run/対象なし/一部enqueue失敗。Scheduler受理・ECS終了・job完了をそれぞれ観測してから有効化する。

## アプリ検証の追跡

| 受入 | 根拠 |
|---|---|
| 環境既定/上書き・Redis/SQS | DefaultQueueConfigTest、SqsQueueBoundaryTest |
| mail/default/webhook/settlement送信先 | SqsQueueBoundaryTest（Laravel driver + AWS MockHandler、Queue fake不使用） |
| receive/delete/release/permanent fail/enqueue例外 | SqsQueueBoundaryTest |
| CLI引数/正常/対象なし/dry-run/障害/sync | OperationalCommandsTest（Artisan公開API） |
| schedule/旧HTTP route撤去 | QueueRuntimeContractTest |
| 既存業務/Google依存/品質 | task check、関連既存tests、composer validate |
| 本番signal/exit | container:verify（ローカルDocker/Redis）。実AWS停止・SQS/DLQは未実行ゲート（上記） |
