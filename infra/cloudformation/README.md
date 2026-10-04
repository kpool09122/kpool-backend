# ネットワーク・データ基盤（Issue #668）

運用担当者向けの東京リージョン `ap-northeast-1` 用構成です。AWS への適用は別途行います。ECS サービス、OIDC、アプリの SQS/S3 アダプタは含みません。

## 構成と作成順序

`root.yaml` をエントリポイントとして `aws cloudformation package` で子テンプレートをアップロードします。子スタックを個別に作成・更新せず、root の change set から管理します。

| テンプレート | 責務・依存 |
| --- | --- |
| `network.yaml` | VPC、2 AZ に public 2 / data 2 subnet、IGW、route table、アプリ・DB・cache 用 SG |
| `data.yaml` | Network の subnet / SG を受け取り、RDS PostgreSQL と Valkey Serverless を作成 |
| `storage.yaml` | Network と独立した S3、CloudFront OAC、SQS / DLQ |
| `root.yaml` | Network → Data の順序、Storage の並行作成、後続スタック向け出力を管理 |

VPC は `10.20.0.0/16`。public は `10.20.0.0/24` / `10.20.1.0/24`、data は `10.20.10.0/24` / `10.20.11.0/24` です。各ペアは `Fn::GetAZs` の先頭 2 AZ を使います。導入前に CIDR の重複と対象アカウントで利用できる AZ を確認してください。CIDR や AZ の変更はデータ移行を伴う可能性があるため、稼働後に置換を承認しないでください。

public のみ IGW へのデフォルトルートを持ちます。data は AZ ごとの route table と VPC 内ルートのみです。NAT、VPC endpoint はありません。public subnet も public IP の自動付与は無効です。後続 ECS で public subnet を使用する場合は task の public IP 設定を明示し、private で使用する場合は ECR / S3 / Logs / Secrets Manager / SQS 等への接続方式を別途設計します。

アプリ SG は初期 ingress なし、egress は HTTPS および DB SG の 5432、cache SG の 6379–6380 のみです。DB/cache はアプリ SG からだけ接続できます。データ SG の loopback 宛 egress は EC2 の既定 allow-all を抑止するための設定です。応答通信は SG の stateful 動作で許可されます。後続の compute スタックが必要な ingress を追加し、この SG を付与できる IAM 権限も制限してください。

## Parameters

`parameters.production.json` は秘密情報を含まない本番初期設定です。環境ごとにコピーして管理します。`ImageDomainName` と `ImageCertificateArn` は未確定のため空欄です。適用前に実際の値を入力してください。空欄のままではパラメーター制約で失敗します。

| root Parameter | 既定値・用途 |
| --- | --- |
| `ResourcePrefix` | `kpool-prod`。英小文字で開始する 3–20 文字の英小文字・数字・ハイフン。同一アカウント・リージョン内で一意にする。Valkey 名とユーザー ID に使うため稼働後は固定 |
| `DeploymentRegion` | `ap-northeast-1` のみ。Rules で実際のリージョンと一致を確認 |
| `ImageDomainName` | 必須・既定値なし。公開画像用の独自ホスト名（例: `images.example.com`）。証明書の対象と一致させる |
| `ImageCertificateArn` | 必須・既定値なし。上記ホスト名をカバーする `us-east-1` の発行済み ACM 証明書 ARN |
| `WorkQueueName` | `kpool-prod-work-v1`。SQS の物理名。置換が必要な更新では `-v2` など未使用の名前へ変更し、consumer 切替後に Retain された旧 queue を廃止 |
| `DatabaseInstanceClass` | `db.t4g.micro`。`db.t4g.small` / `db.t4g.medium` に変更可能 |
| `DatabaseMultiAZ` | 文字列 `false`。可用性要件に応じて `true` に変更 |
| `DatabaseDeletionProtection` | 文字列 `true`。意図した廃止時だけ別の更新で解除 |

Data 子スタックには `DataSubnetIds`、`DatabaseSecurityGroupId`、`CacheSecurityGroupId` を、Storage 子スタックには `WorkQueueName`、`ImageDomainName`、`ImageCertificateArn` を root から渡します。子スタックへ直接入力する必要はありません。Network 子スタックに Parameters はありません。

PostgreSQL は 16 系、gp3 20 GiB、最大 100 GiB のストレージ自動拡張、暗号化、7 日バックアップです。自動 minor upgrade は有効、major upgrade は無効です。バックアップは UTC 17:00–17:30（JST 02:00–02:30）、maintenance は日曜 UTC 18:00–19:00（月曜 JST 03:00–04:00）です。`rds.force_ssl=1` とし、クライアントも RDS CA を使用した `sslmode=verify-full` を設定します。

## Outputs と後続スタック

root は次の値を公開します。秘密値はありません。Export/ImportValue の固定依存を作らず、運用パイプラインから後続スタックの Parameters に渡します。後続の IAM/OIDC スタックはこれらの ARN を使って対象リソースを限定し、必要なアクションだけ許可してください。

| Outputs | 用途 |
| --- | --- |
| `VpcId`, `PublicSubnetIds`, `DataSubnetIds` | VPC とカンマ区切りの subnet ID |
| `ApplicationSecurityGroupId`, `DatabaseSecurityGroupId`, `CacheSecurityGroupId` | task 接続・追加ルールの参照先 |
| `DatabaseEndpoint`, `DatabasePort`, `DatabaseName`, `DatabaseIdentifier` | DB 接続先・運用識別子 |
| `DatabaseSecretArn` | RDS 管理の master credential secret ARN |
| `CacheEndpoint`, `CachePort`, `CacheArn`, `CacheUsername`, `CacheSecretArn` | Valkey TLS 接続先、ユーザー名、secret ARN |
| `PublicImagesBucketName`, `PublicImagesBucketArn` | 公開画像保存先。S3 自体は非公開 |
| `PrivateFilesBucketName`, `PrivateFilesBucketArn` | 非公開ファイル保存先。CloudFront origin には登録しない |
| `ImageDistributionId`, `ImageBaseUrl` | invalidation 対象と独自ドメインの HTTPS 配信 URL |
| `QueueUrl`, `QueueArn`, `DeadLetterQueueUrl`, `DeadLetterQueueArn` | producer / consumer / DLQ 運用の参照先 |

## オフライン検証

Python 3.13、pip、Task を利用します。初回の依存インストールにはインターネットが必要ですが、検証自体は AWS 認証情報・AWS API・ネットワークを必要としません。

```bash
python3.13 -m venv /tmp/kpool-cfn313
/tmp/kpool-cfn313/bin/pip install -r scripts/cloudformation/requirements.txt
PATH=/tmp/kpool-cfn313/bin:$PATH task infra:validate
```

Task がない場合は `PATH=/tmp/kpool-cfn313/bin:$PATH bash scripts/cloudformation/validate.sh`。cfn-lint は東京のスキーマで全 4 テンプレートを検査し、warning 以上で失敗します。例外は root の W3002 のみで、package 前提のローカル子テンプレート参照に限定した除外です。unit test はネットワーク分離、暗号化、保持、OAC の対象、redrive、子スタック Parameters/Outputs の整合性を検査します。サービスの capacity、権限、quota、実際の復旧・疎通は静的検証の対象外です。

## Package と change set による適用

以下は将来の適用時の手順です。この実装作業では実行しません。AWS CLI v2 と承認済みの実行権限を用意し、`aws sts get-caller-identity` でアカウントを確認します。テンプレート用 S3 バケットは事前に東京で用意し、非公開・暗号化・versioning を有効にしてください。テンプレート履歴は rollback 用に保持します。バケット作成やデプロイ IAM/OIDC は本 Issue の対象外です。

画像配信用の独自ドメインを確定し、そのホスト名をカバーする ACM 証明書を **`us-east-1`** で発行・DNS 検証して、両パラメーターを入力します。証明書の作成や DNS の変更は本テンプレートでは行いません。配備後、独自ドメインの CNAME / Alias を CloudFront distribution のドメインへ向け、TLS 1.2 以上での疎通と TLS 1.0 / 1.1 の拒否を確認します。Cloudflare を使う場合の DNS / proxy 設定は #671 で扱います。

root stack 名は、生成される子スタック名・OAC 名・SQS 名の長さ制限に収まるよう **20 文字以内**にします。

先に bootstrap を管理者本人の権限で適用し、Output `CloudFormationExecutionRoleArn` の実値を `CFN_EXECUTION_ROLE_ARN` に設定します。root の Change Set はその実行ロールを指定し、`--include-nested-stacks` で子スタックもレビュー対象に含めます。

```bash
export AWS_REGION=ap-northeast-1
STACK_NAME=kpool-prod-base
ARTIFACT_BUCKET=your-existing-template-bucket
CHANGE_SET=foundation-$(date +%Y%m%d%H%M%S)
mkdir -p build/cloudformation

aws cloudformation package \
  --template-file infra/cloudformation/root.yaml \
  --s3-bucket "$ARTIFACT_BUCKET" --s3-prefix foundation \
  --output-template-file build/cloudformation/packaged.yaml

aws cloudformation create-change-set \
  --stack-name "$STACK_NAME" --change-set-name "$CHANGE_SET" \
  --change-set-type CREATE --include-nested-stacks \
  --role-arn "$CFN_EXECUTION_ROLE_ARN" \
  --template-body file://build/cloudformation/packaged.yaml \
  --parameters file://infra/cloudformation/parameters.production.json

aws cloudformation wait change-set-create-complete \
  --stack-name "$STACK_NAME" --change-set-name "$CHANGE_SET"
aws cloudformation describe-change-set \
  --stack-name "$STACK_NAME" --change-set-name "$CHANGE_SET"
```

更新は同じ手順で `--change-set-type UPDATE` を使用します。root だけでなく `Changes[].ResourceChange.ChangeSetId` が示す子 change set も確認し、置換・削除・SG の拡張・bucket policy・DB の再起動を評価します。変更なしの change set は FAILED になるため `StatusReason` を確認します。Data の置換は自動データ移行ではありません。復元先や移行・切り戻し手順を準備してから承認してください。

CREATE change set 作成後、実行前に root の termination protection を有効にします。root の保護は子スタックにも適用されます。確認後にのみ change set を実行します。

```bash
aws cloudformation update-termination-protection \
  --stack-name "$STACK_NAME" --enable-termination-protection
aws cloudformation execute-change-set \
  --stack-name "$STACK_NAME" --change-set-name "$CHANGE_SET"
aws cloudformation wait stack-create-complete --stack-name "$STACK_NAME"
aws cloudformation describe-stacks --stack-name "$STACK_NAME" \
  --query 'Stacks[0].{TerminationProtection:EnableTerminationProtection,Outputs:Outputs}'
```

UPDATE の完了待ちは `stack-update-complete`。保護設定はテンプレート内の属性ではないため、毎回 root の状態を確認します。termination protection は更新によるリソース削除を防ぎません。更新保護には change set レビューと限定した実行権限が必要です。

## データ保持・廃止・復旧

- root の nested stack 自体は保持しません。root の削除時には子スタックも通常の順序で削除され、保持が必要な RDS / Valkey / secret / S3 / SQS は各子テンプレートの policy に従います。これによりネットワークや CloudFront は依存解消後に削除でき、データリソースだけが残ります。保持されたリソースは課金が継続するため、物理 ID を記録して移管・import・廃止手順を個別に計画します。
- RDS リソース自身は両 policy とも `Snapshot`。DB の削除保護を解除した上で実際に削除・置換すると最終 snapshot を残します。自動バックアップも削除を抑止しますが保持期間は 7 日です。手動 snapshot は別途保持費用が発生します。復元は別 DB の作成・接続先切替を伴い、テンプレートの再実行だけでは復元しません。
- Valkey/cache user/user group/secret は Retain。日次 snapshot を 7 日保持します。データ subnet を廃止する前に cache の ENI と依存関係を整理してください。
- S3 の両バケットと bucket policy は Retain、versioning 有効。オブジェクトおよび旧 version の期限削除は設定しません。7 日経過した未完了 multipart upload のみ破棄します。削除マーカー・旧 version も課金対象であり、廃止には全 version の棚卸しが必要です。
- SQS / DLQ は Retain ですが、メッセージ保持期間は通常 4 日 / DLQ 14 日です。Retain はメッセージの無期限保存ではありません。QueuePolicy は queue と同じく保持し、HTTPS 強制を維持します。
- Storage 子スタックを直接廃止すると CloudFront は削除されますが bucket は残ります。保持された画像 bucket policy の配信 ARN は旧 distribution を指すため、再構築時に更新します。

廃止は依存する compute 側を停止し、復旧可能なバックアップを確認してから行います。root の termination protection 解除と DB の deletion protection 解除は別操作です。保護解除だけでは Retain リソースは削除されません。DB の restore drill、S3 version 復旧、DLQ の回収を運用開始前に確認してください。

## Secret とアクセス管理

RDS は `ManageMasterUserPassword` により Secrets Manager で生成・管理します。master secret を通常のアプリ接続に広く配布せず、後続作業で専用 DB ユーザーと最小権限を準備します。RDS のローテーションに対応して secret を再取得する設計、または task 再起動手順が必要です。DB 削除時には RDS 管理 secret も削除されるため、snapshot 復元時には新しい認証情報を設定します。

Valkey は Secrets Manager で 64 文字のランダム値を生成し、CloudFormation の dynamic reference でユーザーに設定します。Valkey の user group を設定することで既定の無認証ユーザーを無効化します。接続には `application` ユーザーと TLS が必要です。ACL は全 key に対する通常操作を許可し、dangerous カテゴリを除外しています。後続アプリで必要なコマンドとの互換性を確認してください。

Valkey secret の自動 rotation は未設定です。secret の値だけを変更しても既存 user の password は自動同期しません。運用で新旧 2 password の期間を設け、Secrets Manager の version 指定を含む dynamic reference 更新と user 更新を change set に反映し、client 切替後に旧 password を除去してください。ローテーション処理自体は別作業です。

秘密値をテンプレート・Parameters・Outputs・ログ・リポジトリに記録しません。task の secret 注入には実行 role の対象 ARN に限定した `secretsmanager:GetSecretValue` 等を後続で付与します。S3/SQS への業務操作は task role に限定します。本テンプレートは IAM role、OIDC trust、公開 write 権限を作成しません。

## 制限と運用上の判断

RDS の初期構成は Single-AZ のため AZ 障害時の自動フェイルオーバーはありません。Valkey Serverless は 2 AZ の subnet を指定し、サービス標準の転送時・保存時暗号化を使います。RDS/Secrets Manager は AWS 管理 KMS、S3 は SSE-S3、SQS は SSE-SQS です。顧客管理 KMS key は作成しません。

SQS は standard queue で重複・順序入替があり得ます。consumer の冪等性と `VisibilityTimeout=300` 秒より短い処理時間、または visibility 延長を後続で設計します。5 回の受信失敗で DLQ に送ります。DLQ はこの queue からだけ redrive を受け付けます。監視、DLQ アラーム、手動 redrive 権限は別途整備します。

画像 bucket の全オブジェクトは CloudFront 経由で公開されます。非公開ファイルを保存しないでください。非公開ファイルは認可後の署名 URL 等を後続実装で提供します。画像配信は独自ドメインと `us-east-1` の ACM 証明書、SNI、`TLSv1.2_2021`、GET/HEAD、HTTPS リダイレクトと managed cache/security headers policy を使います。WAF・アクセスログは未設定です。オブジェクト名を不変にし、差替えは新しい key を使います。削除や緊急公開停止は cache TTL と invalidation も考慮します。

監視・通知、コスト予算、性能試験、実 AWS 上の権限・quota・疎通と復旧検証は後続の運用準備で実施してください。Serverless の利用量上限は未設定のため、負荷に応じて課金が増加します。

## AWS 仕様の参照

- [RDS DBInstance / 管理 secret](https://docs.aws.amazon.com/AWSCloudFormation/latest/TemplateReference/aws-resource-rds-dbinstance.html)
- [ElastiCache ServerlessCache](https://docs.aws.amazon.com/AWSCloudFormation/latest/TemplateReference/aws-resource-elasticache-serverlesscache.html)
- [Serverless の TLS](https://docs.aws.amazon.com/AmazonElastiCache/latest/dg/in-transit-encryption.html)
- [Valkey user group と既定ユーザーの無効化](https://docs.aws.amazon.com/AmazonElastiCache/latest/dg/Clusters.RBAC.html)
- [CloudFront ViewerCertificate / ACM と TLS ポリシー](https://docs.aws.amazon.com/AWSCloudFormation/latest/TemplateReference/aws-properties-cloudfront-distribution-viewercertificate.html)

## ECS / OIDC の追加スタック (#673)

[本番運用・配備契約](../../doc/infrastructure/operations.md) を参照。root の Network / Data / Storage を一度だけ適用し、その Outputs を integration / runtime に渡す。子テンプレートを別スタックで重複作成しない。PostgreSQL は major 16 を維持し、cfn-lint 1.47.0 の検証に合わせて minor 16.13 を明示している。適用前に東京の提供 minor と既存 DB の実 version を確認し、downgrade しない。
