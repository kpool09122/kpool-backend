# S3画像保存・CloudFront配信と非公開書類

## 設定とCloudFormation Outputs

| 環境変数 | 本番値 / Output | ローカル既定 |
| --- | --- | --- |
| `IMAGE_STORAGE_DISK` | `s3` | `public` |
| `VERIFICATION_DOCUMENTS_DRIVER` | `s3` | `local` |
| `AWS_PUBLIC_IMAGES_BUCKET` | `PublicImagesBucketName` | 未設定 |
| `AWS_PRIVATE_FILES_BUCKET` | `PrivateFilesBucketName` | 未設定 |
| `IMAGE_BASE_URL` | `ImageBaseUrl` (HTTPS、末尾 `/` は任意) | 未設定 |
| `AWS_DEFAULT_REGION` | 配備リージョン | `ap-northeast-1` |

Outputsは `infra/cloudformation/root.yaml` / `storage.yaml` から取得する。
API・worker・schedulerの実行環境に同じ設定を注入する。ローカルのDocker Composeは開発用であり、S3検証時は必要な環境変数を `docker-compose run -e ...` で明示する。
ECSでは `AWS_ACCESS_KEY_ID` / `AWS_SECRET_ACCESS_KEY` / `AWS_SESSION_TOKEN` を設定せず、SDKの認証チェーンでタスクロールを使用する。ローカルで実AWSを検証する場合のみ、一時認証情報とセッショントークンを注入する。認証情報をGitへ保存しない。
バケット名は必ず別々のOutputを使用する。`IMAGE_BASE_URL` はCloudFrontのURLであり、S3バケットURLではない。本番配備前に全設定が揃っていることを確認し、Laravel設定キャッシュは実行環境の変数を注入後に構築する。

## キーと配信境界

- 画像のDB相対キー: `images/<UUID>.webp`。選択diskで保存・URL生成・削除する。公開画像バケットのroot接頭辞は付けない。
- 書類のDB相対キー: `accounts/<account UUID>/<type>_<UUID>.<extension>`。既存disk名 `verification-documents` を維持し、S3 disk rootが `verification-documents` を付加する。実キーは `verification-documents/accounts/...`。DBへバケット名・完全URL・二重接頭辞を保存しない。
- 両バケットはBlock Public Access + BucketOwnerEnforced。Flysystem既定の `private` ACLはACL無効バケットでは拒否されるため、許可された `bucket-owner-full-control` を使用する。public-readは使用せず、公開アクセスは画像バケットへのCloudFront OACのみに限定する。
- 書類バケットをCloudFront originに追加しない。閲覧は本人用／審査用APIの認可後にストリーム応答し、`Cache-Control: private, no-store` を維持する。書類の公開URLやリダイレクトは返さない。
- `runtime.yaml` のAPIタスクロールは公開画像バケットの `images/*`、非公開バケットの `verification-documents/*` に `s3:GetObject`, `s3:PutObject`, `s3:DeleteObject` を限定する。書類はAPI経由のみ。画像取込workerの権限も `images/*` のみ。APIとworkerには各保存対象のみに `s3:PutObjectAcl` を許可し、`s3:x-amz-acl = bucket-owner-full-control` に限定する。APIタスクには非公開書類バケットARNのみに `s3:ListBucket` を許可し、欠損書類のHeadObjectが404を返せるようにする。Laravelの `exists()` は欠損ファイルに対してListObjectsV2によるディレクトリ存在検査も行うため、同権限でこの検査も許可する。HeadObjectには `prefix` パラメータがないため、`s3:prefix` 条件は付けない。この権限は同バケット内のキー一覧も許可するため、書類専用バケットを維持する。workerにListBucketは付与しない。
- S3書き込み例外と、diskが `false` を返す書き込み失敗は呼び出し元へ伝播し、成功パスを返さない。画像／書類の既存コミット後削除、書類の保存ロールバック時削除は変更しない。

## ローカル / testing

`.env.example` をコピーし、`IMAGE_STORAGE_DISK=public`, `VERIFICATION_DOCUMENTS_DRIVER=local` のまま使用する。画像は `public/storage/images` へ保存し、`APP_URL/storage/images/...` で配信する。書類は `storage/app/verification-documents/accounts/...` に保存し、公開storageへのsymlinkを作らない。既存先頭スラッシュ画像パスは既存アプリURLとして扱う。

```sh
task install
task test filter=ImageServiceTest
task test filter=DocumentStorageServiceTest
task test filter=DocumentStreamingTest
task test-no-db filter=S3AdapterContractTest
task check
```

自動テストはStorage fakeとAWS SDK MockHandlerを使用する。後者は実Flysystem S3アダプターのPut/Get/Head/Deleteリクエストでバケット・接頭辞・ACLを検証し、AWSへの通信は行わない。IAM・OAC・DNSの実動作検証とは別である。

## #157 実AWS受け入れ手順（基盤・アプリ配備後）

CloudFormationの適用と実AWS検証はこのIssueでは行わず、#157で実施する。操作は検証用アカウントと検証用データに限定する。

1. スタックOutputsを取得し、上表をAPI／画像取込workerへ注入する。タスク内に永続アップロード領域が不要なこと、タスクロール・バケット名・リージョン・CloudFront URLを確認する。
2. 既存の認証付き画像upload APIへPNG/JPEGを送り、外部URL取込も実行する。DB相対キーが `images/<UUID>.webp`、S3実キーが一致、Content-Typeが `image/webp`、最大辺1024px、透過・アスペクト比が維持され、API画像URLがImageBaseUrlで始まることを確認する。
3. `curl -i "$IMAGE_BASE_URL/images/<検証UUID>.webp"` で200と画像を確認する。未署名 `curl -i "https://$AWS_PUBLIC_IMAGES_BUCKET.s3.$AWS_DEFAULT_REGION.amazonaws.com/images/<検証UUID>.webp"` は403になることを確認する。
4. 検証用本人確認書類を既存upload APIへ送る。書類バケットの `verification-documents/accounts/...` のみへ保存され、画像バケットには混入しないことを運用ロールで確認する。DBは `accounts/...` のままであることを確認する。
5. 書類の匿名S3 GETは403、`curl -i "$IMAGE_BASE_URL/verification-documents/accounts/<検証キー>"` は取得不可 (403/404、書類本文なし) であることを確認する。S3の存在検査はHeadObjectを使う。検証用書類のDBレコードを残してS3オブジェクトだけ削除し、本人用／審査用APIが404を返すことを確認する。権限拒否による403を欠損扱いしない。
6. 本人用APIは本人のみ200、他アカウント／未認証は拒否。審査用APIは許可された審査者のみ200、権限なしは403。200応答がストリームで `Cache-Control: private, no-store`、Locationヘッダーなし、書類本文が一致することを確認する。
7. 画像／書類の更新・削除を行い、DBコミット後に旧S3キーが読めなくなること、ロールバック時は旧キーが残り新書類が削除されることを確認する。権限拒否・書込み失敗は成功応答にならないことを確認する。タスク再起動後も既存ファイルを取得できることを確認する。
8. APIタスクが別バケットや許可接頭辞外へアクセスできないことを、検証用キーで確認する。権限を拡張してテストを通さない。

## 削除とCloudFrontキャッシュ

S3削除はCloudFrontの即時失効ではない。UUIDキーを再利用せず、画像変更時は新URLを参照する。削除済み画像がTTLまでキャッシュに残ることがある。緊急時は運用ロールで対象パスを失効する (アプリタスクには失効権限を付けない)。

```sh
aws cloudfront create-invalidation --distribution-id "$IMAGE_DISTRIBUTION_ID" --paths '/images/<検証UUID>.webp'
aws cloudfront wait invalidation-completed --distribution-id "$IMAGE_DISTRIBUTION_ID" --id '<返却されたInvalidation.Id>'
```

`IMAGE_DISTRIBUTION_ID` は `ImageDistributionId` Output。ワイルドカード全件失効は不要。失効完了後のGETも確認する。即時失効の自動化は対象外。バケットVersioningによりDeleteObjectは削除マーカーとなり旧versionが残るので、書類の完全消去／保持期限は運用ポリシーで別途管理する。

権限仕様: [PutObjectの必要権限](https://docs.aws.amazon.com/AmazonS3/latest/userguide/using-with-s3-policy-actions.html)、[HeadObjectの欠損時応答](https://docs.aws.amazon.com/AmazonS3/latest/API/API_HeadObject.html)。
