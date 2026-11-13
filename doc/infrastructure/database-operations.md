# CloudShell からの本番 DB 操作

補正担当者は東京リージョンの AWS コンソールで CloudShell の VPC 環境を開き、`psql` で非公開の RDS PostgreSQL に接続する。業務テーブルの `SELECT`・`INSERT`・`UPDATE`・`DELETE` を対象とする。

## 1. 基盤と IAM の設定

[基盤の更新手順](operations.md#2-初期構築と基盤変更)に従い、root スタックの変更セットを確認して適用する。root の Outputs から `VpcId`、`DataSubnetIds`、`DatabaseOperatorSecurityGroupId`、`DatabaseEndpoint`、`DatabasePort`、`DatabaseName` を控える。

AWS 管理者は、補正担当者専用の IAM ロールまたは IAM Identity Center の許可セットに、以下のインラインポリシーを設定して担当者に割り当てる。`ACCOUNT_ID` は AWS アカウント ID、`VPC_ID` と `OPERATOR_SG_ID` はそれぞれ `VpcId` と `DatabaseOperatorSecurityGroupId` の値、`DATA_SUBNET_A` と `DATA_SUBNET_B` は `DataSubnetIds` の２つの値に置換する。

IAM の Allow は他のポリシーと合算される。専用ロールにはこのポリシーを使い、既存の CloudShell・EC2 権限も含めて実効権限を確認する。運用者用のセキュリティグループを使える人は、AWS 管理者と補正担当者に限定する。

```json
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Sid": "CreateDatabaseVpcEnvironment",
      "Effect": "Allow",
      "Action": "cloudshell:CreateEnvironment",
      "Resource": "*",
      "Condition": {
        "StringEquals": {"aws:RequestedRegion": "ap-northeast-1"},
        "ForAllValues:StringEquals": {
          "cloudshell:VpcIds": ["VPC_ID"],
          "cloudshell:SubnetIds": ["DATA_SUBNET_A", "DATA_SUBNET_B"],
          "cloudshell:SecurityGroupIds": ["OPERATOR_SG_ID"]
        },
        "Null": {
          "cloudshell:VpcIds": "false",
          "cloudshell:SubnetIds": "false",
          "cloudshell:SecurityGroupIds": "false"
        }
      }
    },
    {
      "Sid": "UseCloudShellEnvironment",
      "Effect": "Allow",
      "Action": [
        "cloudshell:CreateSession",
        "cloudshell:DeleteEnvironment",
        "cloudshell:DescribeEnvironments",
        "cloudshell:GetEnvironmentStatus",
        "cloudshell:StartEnvironment",
        "cloudshell:StopEnvironment"
      ],
      "Resource": "*",
      "Condition": {"StringEquals": {"aws:RequestedRegion": "ap-northeast-1"}}
    },
    {
      "Sid": "ListNetworkChoices",
      "Effect": "Allow",
      "Action": ["ec2:DescribeVpcs", "ec2:DescribeSubnets", "ec2:DescribeSecurityGroups"],
      "Resource": "*",
      "Condition": {"StringEquals": {"aws:RequestedRegion": "ap-northeast-1"}}
    },
    {
      "Sid": "InspectNetworkViaCloudShell",
      "Effect": "Allow",
      "Action": ["ec2:DescribeDhcpOptions", "ec2:DescribeNetworkInterfaces"],
      "Resource": "*",
      "Condition": {
        "StringEquals": {"aws:RequestedRegion": "ap-northeast-1"},
        "ForAnyValue:StringEquals": {"aws:CalledVia": "cloudshell.amazonaws.com"}
      }
    },
    {
      "Sid": "CreateInterfaceInDatabaseNetwork",
      "Effect": "Allow",
      "Action": "ec2:CreateNetworkInterface",
      "Resource": [
        "arn:aws:ec2:ap-northeast-1:ACCOUNT_ID:subnet/DATA_SUBNET_A",
        "arn:aws:ec2:ap-northeast-1:ACCOUNT_ID:subnet/DATA_SUBNET_B",
        "arn:aws:ec2:ap-northeast-1:ACCOUNT_ID:security-group/OPERATOR_SG_ID"
      ],
      "Condition": {
        "ForAnyValue:StringEquals": {"aws:CalledVia": "cloudshell.amazonaws.com"}
      }
    },
    {
      "Sid": "CreateTaggedCloudShellInterface",
      "Effect": "Allow",
      "Action": "ec2:CreateNetworkInterface",
      "Resource": "arn:aws:ec2:ap-northeast-1:ACCOUNT_ID:network-interface/*",
      "Condition": {
        "ForAnyValue:StringEquals": {
          "aws:CalledVia": "cloudshell.amazonaws.com",
          "aws:TagKeys": "ManagedByCloudShell"
        }
      }
    },
    {
      "Sid": "TagInterfaceAtCreationViaCloudShell",
      "Effect": "Allow",
      "Action": "ec2:CreateTags",
      "Resource": "arn:aws:ec2:ap-northeast-1:ACCOUNT_ID:network-interface/*",
      "Condition": {
        "StringEquals": {"ec2:CreateAction": "CreateNetworkInterface"},
        "ForAnyValue:StringEquals": {
          "aws:CalledVia": "cloudshell.amazonaws.com",
          "aws:TagKeys": "ManagedByCloudShell"
        }
      }
    },
    {
      "Sid": "ManageTaggedCloudShellInterfaces",
      "Effect": "Allow",
      "Action": ["ec2:CreateNetworkInterfacePermission", "ec2:DeleteNetworkInterface"],
      "Resource": "arn:aws:ec2:ap-northeast-1:ACCOUNT_ID:network-interface/*",
      "Condition": {
        "StringEquals": {"aws:ResourceTag/ManagedByCloudShell": ""},
        "ForAnyValue:StringEquals": {"aws:CalledVia": "cloudshell.amazonaws.com"}
      }
    }
  ]
}
```

[AWS の CloudShell VPC 用 IAM 権限](https://docs.aws.amazon.com/cloudshell/latest/userguide/aws-cloudshell-vpc-permissions-1.html)

## 2. CloudShell から TLS で接続する

1. AWS コンソールを東京リージョンで開き、CloudShell の `+` から **Create VPC environment** を選ぶ。root の Outputs にある VPC、データ用サブネットのいずれか１つ、運用者用のセキュリティグループ１つを指定する。
2. 手元の端末で AWS 公式の[東京リージョン用 RDS CA バンドル](https://truststore.pki.rds.amazonaws.com/ap-northeast-1/ap-northeast-1-bundle.pem)を取得する。データ用サブネットにはインターネットへの経路がないため、CloudShell のターミナルに PEM の全文を貼り付けて下記のファイルを作る。セッション終了後はファイルが消えるため、次のセッションで再作成する。
3. `psql --version` で同梱クライアントを確認し、Outputs の接続先と個別 DB ユーザー名を指定して `psql` を実行する。パスワードはプロンプトで入力する。接続後は `\conninfo` で接続先、DB 名、ユーザー、TLS を確認する。

```sh
cat > "$HOME/rds-ca.pem" <<'RDS_CA_PEM'
ここを AWS から取得した PEM の全文で置き換える
RDS_CA_PEM

PGSSLMODE=verify-full PGSSLROOTCERT="$HOME/rds-ca.pem" PGCONNECT_TIMEOUT=10 \
  psql -X -v ON_ERROR_STOP=1 -h 'DatabaseEndpoint の値' -p 'DatabasePort の値' \
  -d 'DatabaseName の値' -U '個別 DB ユーザー名' -W
```

## 3. DB ロールと個別ユーザーを設定する

初回はマイグレーション適用後に DB 管理者が対象 DB に接続する。既存の管理用接続経路を使うか、上の CloudShell 接続手順で `DatabaseSecretArn` の管理ユーザーを指定する。管理者が Secrets Manager から取得した管理パスワードは `psql` のプロンプトで入力する。

初回だけ、権限をまとめるロールを作る。`\dt public.*` で実 DB の業務テーブルを確認し、下の `GRANT` に列挙する。`migrations`、`jobs`、`job_batches`、`failed_jobs` などの内部管理テーブルは対象外とする。採番に使うシーケンスは `pg_get_serial_sequence` で確認して `USAGE` を付与する。業務テーブルの追加時は、マイグレーション後に対象テーブルと関連シーケンスの権限を追加する。

```sql
BEGIN;
CREATE ROLE kpool_db_operator NOLOGIN;
SET LOCAL ROLE kpool_migration;
GRANT USAGE ON SCHEMA public TO kpool_db_operator;
GRANT SELECT, INSERT, UPDATE, DELETE ON TABLE
  public.announcements, public.invoices, public.invoice_lines
  TO kpool_db_operator;
SELECT pg_get_serial_sequence('public.invoice_lines', 'id');
GRANT USAGE ON SEQUENCE public.invoice_lines_id_seq TO kpool_db_operator;
COMMIT;
```

担当者ごとに個別のログインを作成する。管理ユーザーのまま実行し、例のユーザー名を担当者固有の名前に置換する。`\password` の入力欄で個別パスワードを設定し、承認済みの資格情報共有経路で本人に渡す。接続権限は root の `DatabaseName` が既定の `kpool` の場合の例。

```sql
CREATE ROLE kpool_operator_example LOGIN;
GRANT CONNECT ON DATABASE kpool TO kpool_operator_example;
GRANT kpool_db_operator TO kpool_operator_example;
\password kpool_operator_example
```

担当から外すときは AWS 側の割り当てを解除し、DB 管理者が個別ユーザーを `NOLOGIN` に変更して補正用ロールを取り消す。既存の DB 接続は `pg_stat_activity` の `usename` で確認し、該当セッションを `pg_terminate_backend` で終了する。

## 4. 疎通確認とデータ補正

- 検証環境で担当者用 IAM と個別 DB ユーザーを使い、TLS 接続を確認する。検証データに対して `SELECT`・`INSERT`・`UPDATE`・`DELETE` を実行し、`ROLLBACK` で変更が残らないことを確認する。内部管理テーブルの操作とスキーマ変更が拒否されること、担当外の AWS プリンシパルでは指定の CloudShell 環境を作成できないことも確認する。
- 本番の補正では `BEGIN` で開始し、対象行と想定件数を確認してから補正 SQL を実行する。影響件数と補正後の値を確認し、想定どおりなら `COMMIT`、違えば `ROLLBACK` する。作業チケットに実行者、変更内容、件数、結果を記録する。
