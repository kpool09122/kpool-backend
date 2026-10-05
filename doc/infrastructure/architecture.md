# インフラ構成図

2026-10-05 時点のリポジトリ内の CloudFormation、接続台帳、Docker Compose に基づく図。Fargate は Public subnet＋public IP、受信は用途別 Security Group で制限する構成。AWS / Cloudflare の実環境は照会していないため、配備済み・稼働中であることを示すものではない。台数や接続先の実値は CloudFormation Outputs、ECS、ALB、Scheduler で確認する。

Mermaid 対応の Markdown ビューアで表示・編集できる。矢印は要求・操作の方向を表し、レスポンスの戻り方向は省略する。破線は図ごとのラベルに示す補助経路や予定の接続を表す。

## サービスの全体像

```mermaid
flowchart TB
    user["利用者 / ブラウザ"]
    frontend["Cloudflare Workers<br/>フロントエンド・外部管理<br/>配備方式と bindings は未確定"]
    cdn["CloudFront<br/>画像専用 CDN / HTTPS"]

    subgraph aws["AWS 東京 ap-northeast-1"]
        subgraph vpc["VPC 10.20.0.0/16"]
            subgraph public["Public subnet × 2 AZ"]
                alb["公開 ALB<br/>HTTPS 443"]
                api["ECS Fargate API<br/>nginx + PHP-FPM + Laravel<br/>ARM64 / HTTP 8080"]
                worker["ECS Fargate worker<br/>SQS ジョブ処理"]
            end
            subgraph private["Data subnet × 2 AZ / Internet 既定経路なし"]
                db[("RDS PostgreSQL 18<br/>非公開 / TLS / 初期 Single-AZ")]
                cache[("ElastiCache Serverless Valkey 8<br/>非公開 / TLS / パスワード認証")]
            end
        end
        images[("S3 画像 bucket<br/>bucket 自体は非公開")]
        files[("S3 非公開書類 bucket<br/>認可後に API 経由で配信")]
        igw["Internet Gateway"]
        queue["SQS work queue"]
        dlq["SQS DLQ<br/>処理失敗メッセージ"]
    end

    external["AWS サービス / 外部 API<br/>ECR・Secrets・S3・SQS・Google・Stripe 等"]
    user -->|画面 / HTTPS| frontend
    user -->|API / HTTPS| alb
    frontend -.->|API 接続契約 / 実行方式は未確定| alb
    user -->|画像 / HTTPS| cdn
    alb -->|API SG の受信は ALB のみ| api
    api -->|外向き HTTPS / task の public IP| igw
    worker -->|外向き HTTPS / task の public IP| igw
    igw -->|HTTPS| external
    api -->|SQL / 5432| db
    worker -->|SQL / 5432| db
    api -->|session / cache / TLS| cache
    worker -->|cache / lock / TLS| cache
    api -->|アップロード / 読取| images
    api -->|アップロード / 認可後の読取| files
    cdn -->|OAC 署名付き読取| images
    api -->|ジョブ送信| queue
    worker -->|受信 / 完了時に削除| queue
    queue -->|maxReceiveCount 5| dlq

    classDef edge fill:#fff2df,stroke:#ca8a04,color:#1f2937
    classDef compute fill:#e0f2fe,stroke:#0284c7,color:#1f2937
    classDef data fill:#ecfdf5,stroke:#059669,color:#1f2937
    class frontend,cdn edge
    class alb,igw,api,worker compute
    class db,cache,images,files,queue,dlq data
```

- API / worker の初期 desired count は **0**。図内のサービス経路は稼働用 release task 起動後の構成を表す。
- ALB と API / worker / migration / 定期 task は public subnet に配置し、task の public IP を有効化する。外向き HTTPS は Internet Gateway 経由。NAT Gateway と task 専用 private subnet は定義しない。DB / Valkey と hook 用の data subnet は internet 既定経路を持たない。
- API の受信は ALB SG からの 8080 のみ。worker / migration に受信許可はない。public task は SG の受信制限に依存し、設定を Internet 向けに広げると直接到達し得るため、実 SG と template の一致を確認する。
- S3 / SQS などへの矢印はサービスの操作関係を表し、task からの通信は上記 Internet Gateway 経路を通る。task ごとの public IPv4 料金は発生する。
- 2 AZ の subnet があることと、DB が Multi-AZ で稼働することは別。RDS の `DatabaseMultiAZ` の既定は `false`。
- API / 画像 DNS は接続台帳で初期 **DNS-only** を指定。Cloudflare proxy の有効化は別判断。API 証明書は東京、CloudFront 画像証明書は **us-east-1**。
- Cloudflare Worker の実際の配備方式・bindings は未確定。図の破線は API URL の接続契約を示し、すべてのブラウザ要求が Worker を経由すると断定しない。

### subnet を 2 AZ に分ける理由

| 区分 | 配置と目的 |
|---|---|
| Public × 2 | ALB の通常の AZ 配置は異なる AZ の subnet が最低 2 つ必要。Fargate も両方を起動先に指定する |
| Data × 2 | RDS の DB subnet group は Single-AZ DB でも最低 2 AZ をカバーする。DB / Valkey と private hook を配置する |

合計 4 subnet。2 AZ の起動先を指定しても task が常時 2 台になるわけではなく、DB が Multi-AZ になるわけでもない。台数と冗長化はそれぞれ desired count と DatabaseMultiAZ で設定する。

## 配備と運用の流れ

実線はテンプレートにある接続、破線は接続台帳に記載された未実装の release Pipeline の操作を示す。Blue/Green の新旧 API task が併存するのは配備・bake 中で、通常時の台数は desired count で決まる。ALB と hook 用 VPC Endpoint は常設で、配備の有無にかかわらず料金が発生する。

```mermaid
flowchart LR
    pipeline["GitHub Actions<br/>release Pipeline は未実装"]
    role["用途を限定した配備 IAM Role"]
    ecr["ECR<br/>共通コンテナイメージ"]
    migration["Fargate migration<br/>単発 / DDL 用 DB ユーザー"]
    api["Fargate API<br/>native BLUE_GREEN"]
    worker["Fargate worker<br/>ROLLING"]
    frontend["Cloudflare Workers<br/>フロント配備"]
    db[("RDS PostgreSQL")]
    alb["ALB<br/>Blue / Green target group"]
    hook["Lambda lifecycle hook<br/>artifact 指定時に作成<br/>Data subnet 内"]
    endpoint["EC2 Interface VPC Endpoint<br/>2 AZ / 常設<br/>ALB private IP の探索"]
    secrets["Secrets Manager<br/>app / migration / cache<br/>用途別権限で起動時注入"]
    ssm["SSM Parameter Store<br/>非秘密の接続メタデータ"]
    scheduler["EventBridge Scheduler<br/>既定 DISABLED"]
    scheduled["Fargate 定期処理<br/>schedule:run 雛形"]
    sdlq["Scheduler 専用 SQS DLQ<br/>起動要求の失敗"]
    logs["CloudWatch Logs<br/>用途別ログ"]
    alarms["CloudWatch Alarms<br/>ALB 5xx / 応答時間 / Scheduler DLQ"]
    sns["SNS<br/>通知 topic"]

    pipeline -.->|OIDC| role
    role -.->|build / publish| ecr
    role -.->|配備前に実行・成功確認| migration
    migration -->|schema 更新| db
    role -.->|migration 成功後に更新| api
    role -.->|API 配備後に更新| worker
    pipeline -.->|backend 配備後 / API token| frontend
    ecr -->|同じ image を用途別 command で実行| migration
    ecr --> api
    ecr --> worker
    ecr --> scheduled
    api -->|target 登録 / traffic 切替| alb
    api -->|POST_TEST_TRAFFIC_SHIFT| hook
    hook -->|HTTPS 8443 / test listener 検証| alb
    hook -->|ENI 情報の照会| endpoint
    secrets --> migration
    secrets --> api
    secrets --> worker
    ssm -.->|予定: release の環境変数へ変換| pipeline
    scheduler -->|RunTask| scheduled
    scheduler -->|起動要求の配信失敗| sdlq
    api -->|stdout / stderr| logs
    worker --> logs
    migration --> logs
    scheduled --> logs
    hook --> logs
    alb -->|metrics| alarms
    sdlq -->|滞留数| alarms
    alarms --> sns
    alarms -->|API の配備失敗時 rollback 判定| api
```

Scheduler の既定は毎分の汎用雛形だが、アプリが必要とする 2 用途の周期・送信権限・処理失敗監視は未達。運用文書では、これらを解消してから有効化する。Scheduler DLQ はジョブ処理の失敗や task の非 0 終了を捕捉するものではない。SNS の購読先、hook artifact、外部 canary alarm の実設定も実環境で確認が必要。

## CloudFormation の管理単位

矢印は依存情報・Outputs の受け渡しを表す。基盤変更は管理者の操作ツールが扱い、アプリ用 release Pipeline は基盤を変更しない。

```mermaid
flowchart LR
    admin["管理者<br/>短期 AWS 認証 / infra 操作ツール"]
    bootstrap["bootstrap<br/>GitHub OIDC / 配備 Role<br/>CloudFormation 実行 Role"]
    subgraph root["root 親 stack"]
        network["network 子 stack<br/>VPC / subnet / SG"]
        data["data 子 stack<br/>RDS / Valkey / 管理 Secret"]
        storage["storage 子 stack<br/>S3 / CloudFront / SQS"]
    end
    integration["integration<br/>app・migration Secret 保存先<br/>SSM / account 予算"]
    runtime["runtime<br/>ECR / ECS / ALB / IAM<br/>hook / alarm / Scheduler"]

    admin --> bootstrap
    bootstrap -->|実行 Role| root
    network -->|subnet / SG| data
    data -->|endpoint| integration
    storage -->|bucket / queue / image URL| integration
    network -->|VPC / subnet / SG| runtime
    data -->|接続先 / cache Secret| runtime
    storage -->|bucket / queue| runtime
    integration -->|Secret ARN| runtime
```

## ローカル開発環境

本番とは別に、Docker Compose の `kpool-network` 上で実行する構成。

```mermaid
flowchart LR
    browser["ブラウザ / 開発ツール"]
    tests["PHPUnit<br/>task test"]
    subgraph compose["Docker Compose / kpool-network"]
        nginx["nginx<br/>ホスト 8080 → container 80<br/>APP_PORT で変更可"]
        php["PHP-FPM / Laravel<br/>development image"]
        db[("PostgreSQL 18<br/>ホスト 5432")]
        redis[("Redis 8<br/>ホスト 6379")]
        mail["Mailpit<br/>SMTP 1025 / Web UI 8025"]
        testdb[("テスト用 PostgreSQL 18<br/>ホスト 5433 → 5432")]
        testredis[("テスト用 Redis 8<br/>ホスト 6380 → 6379")]
    end
    browser -->|HTTP| nginx
    nginx -->|FastCGI / 9000| php
    php --> db
    php --> redis
    php -->|SMTP| mail
    browser -->|メール確認 / ホスト 19980| mail
    tests --> testdb
    tests --> testredis
```

ローカル/テストと本番テンプレートは PostgreSQL 18 に揃える。本番はメジャー18を指定し、作成時にRDSが対応minorを選択する。minor自動更新も有効にする。ローカルには本番の ALB、SQS、CloudFront、Valkey Serverless に相当する Compose service は定義されていない。

## 参照元

- [インフラの入口](../../infra/cloudformation/README.md)：構成と管理責任
- [network.yaml](../../infra/cloudformation/network.yaml)、[data.yaml](../../infra/cloudformation/data.yaml)、[storage.yaml](../../infra/cloudformation/storage.yaml)：ネットワークとデータ基盤
- [runtime.yaml](../../infra/cloudformation/runtime.yaml)、[integration.yaml](../../infra/cloudformation/integration.yaml)、[bootstrap.yaml](../../infra/cloudformation/bootstrap.yaml)：実行基盤と権限・設定
- [接続台帳](pipeline-handoff.md)：Cloudflare、DNS、Pipeline、未達ゲート
- [コンテナ実行契約](container-runtime.md)、[queue 契約](queue-operations.md)：実行・非同期処理
- [docker-compose.yml](../../docker-compose.yml)：ローカル構成

構成変更時は対応する図も更新する。実環境の確認結果は [検証票](validation-checklist.md) に記録し、テンプレート既定値と実際の設定を混同しない。
