# 本番コンテナの実行契約（#665 / #156）

## ビルド・検証

```sh
task container:build                 # --platform linux/arm64 --target production
task container:smoke                 # PHP 8.5 / GD WebP / PostgreSQL / Redis / PCNTL / 開発依存除外
task container:verify                # Python 3 + Docker。専用DB/Redis/ネットワークを作成・削除
task check                           # 既存開発用ステージで整形・PHPStan・全PHPUnit
```

production ステージはソース・composer.lock に基づく `--no-dev` vendor・PHP・nginx・MeCabを内包する。Composerはビルド専用で、起動時の取得やホストのソース/vendorマウントは不要。ネイティブ拡張も指定プラットフォーム上でビルドする。PHPUnit、PHPStan実行ツール、CS Fixer、PCOVは含めない（本番ライブラリが依存する `phpstan/phpdoc-parser` は解析データ用ライブラリであり開発ツールではない）。

development ステージが Dockerfile の既定。既存 Compose も `target: development` を明示し、開発用エントリーポイントとPCOV・Composerを維持する。

## #156 へのタスク定義引き渡し

| 項目 | 契約 |
|---|---|
| Architecture / OS | ARM64 / LINUX |
| User | `1000:1000`（非root） |
| API Command | `['api']`（イメージ既定） |
| HTTP | TCP **8080** / `ApiPort=8080` |
| ALB HealthCheckPath | `/health` |
| worker Command | `['php','artisan','queue:work','sqs','--timeout=90','--tries=3','--sleep=3']` |
| migration Command | `['php','artisan','migrate','--force']` |
| EventBridge単発Command | `['php','artisan','schedule:run']` または対象コマンド |
| StopTimeout | **120秒**（API・worker・単発タスク） |
| ReadonlyRootFilesystem | `true` |
| LogConfiguration | `awslogs`。nginxアクセス=stdout、nginx/FPM/Laravel=stderr、artisan/worker=stdout+stderr |
| 必須 writable MountPoints | `/tmp`, `/var/www/html/storage`, `/var/www/html/bootstrap/cache` |

3つの書き込み領域はタスクごとの一時領域で、UID/GID **1000:1000** に書き込み可能にする。`storage` と `bootstrap/cache` は mode 0770、`/tmp` は 1770 を推奨。empty volume は、タスク起動前に所有者・モードを整える初期化コンテナ等を #156 で用意する（rootのままの空volumeを渡すだけでは動かない）。別タスクと共有せず、ソース全体やvendorをマウントしない。ローカル検証は所有者付きtmpfsを使う。FargateはDockerのtmpfsオプションをサポートしないため、ECS MountPointsのempty volume/EFSと初期化手順へ置き換える。アップロードの永続化は別途S3等の外部ストレージを使い、storageへの一時書き込みを永続データと扱わない。

APIはtini -> bash supervisor -> nginx/FPM。SIGTERMを両daemonのSIGQUITに変換して新規受け付けを停止し、処理中リクエストを待つ。子daemonの予期しない終了（0も含む）は残りも停止し、タスクを非0で終了する。tiniが孤児を回収する。nginx shutdown上限100秒、FPM request上限95秒、nginx FastCGI待ち95秒。ECSの120秒猶予を超えない処理時間にする。ALB は idle timeout 120秒、両ターゲットグループの deregistration delay 120秒とする（#669）。既定60秒では95秒の処理を途中で切断し得る。95秒 < nginx shutdown 100秒 < StopTimeout 120秒と整合させる。ALB idle timeout は無通信時間の制限であり処理全体の上限ではない。

worker/単発コマンドは引数を保持してexecされ、tiniが実プロセスへSIGTERMを転送する。PCNTLによりworkerは処理中ジョブの完了を待って終了する。timeout90秒 < StopTimeout120秒。SQS visibility timeoutはジョブtimeoutより十分長くし、キューの配信/再試行契約は `queue-operations.md` を参照。長時間ジョブを設定する場合はtimeout・visibility・停止猶予を合わせて再設計する。API/worker起動にmigrationを含めず、配備前の独立タスクで実行する。失敗時の終了コードをpipelineで検出し、配備を止める。

## ヘルスチェック

`/health` はnginx経由で **Laravel自身** が返す200。DB・Redis・SQS・外部APIの疎通までは検証しないliveness/bootstrapチェックで、nginxの固定レスポンスではない。ルーティング/アプリ起動の例外はLaravel health処理で500となる。ALBは起動待ちを設定する（例：ECS healthCheckGracePeriodSeconds=60、interval=15秒、timeout=5秒、healthyThreshold=2、unhealthyThreshold=3）。起動失敗/daemon停止は接続失敗/非200となり置き換え対象。DB・キューの可用性は別の監視と実API/ジョブの確認で扱う。

## 環境変数・秘密情報

- イメージ既定: `APP_ENV=production`, `APP_DEBUG=false`, `LOG_CHANNEL=stderr`, `LOG_LEVEL=info`。本番でdebugを有効化しない。
- 実行時Secrets: `APP_KEY`, `DB_PASSWORD`、Stripe/WebAuthn/メール/Google等の必要な認証情報。既存 config と `.env.example` を参照し、Secrets Managerから注入する。ビルド引数やDockerfile ENVへ秘密値を渡さない。
- 実行時設定: `APP_URL`, `FRONTEND_URL`, `DB_CONNECTION=pgsql`, `DB_HOST`, `DB_PORT=5432`, `DB_DATABASE`, `DB_USERNAME`, `REDIS_HOST`, `REDIS_PORT=6379`、TLS等の接続設定。
- worker: `QUEUE_CONNECTION=sqs`, `AWS_DEFAULT_REGION`, `SQS_PREFIX`, `SQS_QUEUE`（追加キューはqueue-operations.md参照）。本番AWS認証は用途別タスクロールのECS credentialsを使い、固定アクセスキーをイメージへ入れない。
- 全モードで同じイメージだが、API/worker/migration/schedulerのIAM・DB権限は分離する。

設定にSentryのClosure callbackがあるため `config:cache` は実行しない。route/view cacheも焼き込まず、各起動時に設定を環境変数から読み込む。entrypointはbootstrap/cache内の古いPHPキャッシュを除去し、必要なstorageディレクトリを生成する。package/services manifestはLaravelが必要時に生成する。再利用された設定キャッシュに前のタスクの秘密値が残る問題を避ける。変更後にキャッシュ戦略を導入する場合はClosure対応と秘密値の保存先を改めて確認する。

`.dockerignore` はソース用途のallowlist。`.env*`, auth.json, SSH/AWS/GCloud認証、ホストvendor、テスト、coverage、git、bootstrap/cache生成物はビルドcontextへ渡さない。Dockerfileもソースディレクトリを明示COPYし、ホストstorageをコピーしない。公開ディレクトリのsymlink `public/storage` も除外する。ソースに秘密値を埋め込まないことは別途レビューが必要。

## ローカル実行例

秘密を含むenvfileはリポジトリ外に置き、mode 0600にする。コンテナから到達できる検証用DB/Redisを指定する（ホストのlocalhostはコンテナ自身）。

```sh
task container:run envfile=/absolute/path/runtime.env                 # API: localhost:18080
task container:run envfile=/absolute/path/runtime.env -- php artisan queue:work redis --timeout=90 --tries=3
task container:run envfile=/absolute/path/runtime.env -- php artisan migrate --force
task container:run envfile=/absolute/path/runtime.env -- php artisan schedule:run
curl -i http://localhost:18080/health
# Ctrl-C / docker stop は SIGTERM、最大120秒で停止
```

`container:verify` はイメージのみを使ってread-only root・cap-drop ALL・no-new-privilegesの全モードを検証し、DB migration、API health=200/CSRF=204/auth=401、単発成功/失敗、ログ、パッケージ内容、Redis実ジョブと処理中停止、API処理中停止、daemon異常終了を確認する。APIの処理中停止はRedis待ちの実HTTPリクエストで確認する。専用PostgreSQL18/Redis8とネットワークは終了時に削除し、既存開発サービスへ書き込まない。

SQSアプリ対応は #666 / PR #676 でmainに導入済み。本Issueのローカル検証はRedis実ジョブでworkerの実行/停止を確認する。実AWSのSQS送受信・IAM・visibility/retry/DLQおよびECS/ALB/draining/volume初期化の確認は #157/#156 の実環境検証で行う。本番イメージが起動できることと、AWS SQSの実ジョブ処理が成功したことを混同しない。
