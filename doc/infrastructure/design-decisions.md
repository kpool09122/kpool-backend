# インフラの設計判断・保証・残余リスク

本書は現在の採否とその根拠の正本。全体像と設定は[入口](../../infra/cloudformation/README.md)、操作は[運用手順](operations.md)、担当・外部成果物は[接続台帳](pipeline-handoff.md)。

## 評価の範囲と証拠

Issue #682 で `bootstrap/root/network/data/storage/integration/runtime`、環境入力・state・plan/execute、検証入口・CI、運用/コンテナ/queue/S3文書とテストを再評価した。旧設計の追認やテスト数の維持を目的にしない。

- 根拠はリポジトリのテンプレート、実装、オフライン検証。今回AWSの照会・適用、Cloudflareの照会・変更は行っていない。稼働環境の有無、drift、資源状態、実効権限、費用は**未確認**。
- 過去のSTS応答/AccessDeniedや過去の演習結果は今回の実環境確認ではない。環境が無いとも配備可能とも推測しない。
- #156 は評価時点で OPEN、リポジトリのCIは品質検証のみ。通常配備の記述はPipelineへの要求であり、動くrelease workflowの証拠ではない。

## 構成案の比較

| 案 | 得られる簡素さ・保証 | 運用負荷・リスク | 判断 |
|---|---|---|---|
| 全資源を1stackへ統合 | 接続入力が減り、1回で更新できる | trustの起点、長期データ、computeを同じ変更/rollback範囲に置く。既存資源の所属移動/importが必要 | 不採用。既存環境のinventoryなしで移す利益が移行リスクを上回らない |
| bootstrap + その他1stack | 管理者/OIDC境界を残して親操作を減らす | IAM境界自体は守れるが、Secret保存先・データ・computeの変更失敗を分離しにくい。所有移行は依然必要 | 不採用。小規模新環境では候補だが、今は移行の必要性を示せない |
| 現在の4親 + root内の3子 | bootstrapを管理者だけで更新し、データ基盤/設定保存先/computeの更新を分離。子は1親操作で依存解決 | package bucketと4親の順序が必要。stack分割だけでアクセス制御/データ保護が成立するわけではない | 採用。bootstrap IAM、用途別role、保持policyで実際の境界を作る。子は独立した運用単位にしない |
| サービス・alarm・IAMまで細分化 | 個々の更新をさらに分離 | Outputs/順序/変更レビュー/復旧責任が増える。具体的な独立運用者や頻度差がない | 不採用。新しい親や独自配備フレームワークは追加しない |

root内のnetwork/data/storageは責務別のテンプレート分割であり、単独配備は禁止。分割自体はAWS資源の固定費を減らさない。integrationをruntimeへ移すことも技術的には可能だが、app/migration Secretと非秘密SSMの保存先をcomputeの変更から分け、既存所有を動かさない方を選ぶ。

### ネットワーク・compute・費用

- 初期運用の費用を抑えるため、ALBとFargateはpublic subnetに配置し、taskのpublic IPを明示的に有効化する。外向きHTTPSはInternet Gateway経由とし、NATの時間/処理料金を追加しない。APIの受信はALB SGから8080のみ、worker/migrationには受信許可を設けない。DB/cacheとprivate hookはinternet既定経路のないdata subnetに置く。
- public配置はSGの受信制限に依存する。受信元をInternetへ広げるとtaskへ直接到達できるため、template/testと実SGの照合でALB限定・用途別の受信制限を維持する。taskごとのpublic IPv4は課金対象。HTTPS egressは宛先allowlistではなく、侵害後の外部送信を防ぐものではない。private task + NATはSG誤設定時の直接到達を防ぐ追加の防御として、将来の要件・予算に応じて再評価する。AWS用endpointのみではGoogle/Stripe等の外部APIを代替できない。
- private hookのEC2 interface endpointはALB ENI発見のため。ALBの8443はhook SGだけを許可し、hookのHost/SNI/TLS検証は外部コードの受入が必要。endpointの利用API/有効性を実hookで確かめるまで削除・拡張しない。
- API native BLUE_GREENを維持し、切替前smokeと切替後rollbackを分離する。新旧taskの併存費用はdeployment/bake中に発生し、通常時にAPIを常時二重化する設定ではない。ALBとhook用EC2 interface endpointは配備の有無にかかわらず固定費が続き、hook/canaryの実行費用は別に扱う。workerはROLLINGを維持。
- RDS Single-AZは費用を抑える初期選択で、自動failover/SLAの保証ではない。backupを高可用性と同一視しない。Multi-AZ/容量変更は復旧目標・実測に基づき管理者が選ぶ。Valkey Serverlessは容量管理を減らす一方、最小課金/処理費と復元検証が残る。
- ALB、Fargate旧新task、public IPv4、Valkey、endpoint、canary、CloudFront転送、logs、S3 versions、Retain資源を費用台帳に含める。Budgetsはaccount全体の通知であり上限強制でもk-pool単体の原価でもない。Cloudflare/外部APIは別集計。見積金額・負荷実測なしに「最安」とは判断しない。

## セキュリティ・データ保護の判断

| 防ぐリスク | 現在の境界・維持理由 | 限界/稼働前に確認する事項 |
|---|---|---|
| Actionsから基盤/管理者権限へ昇格 | OIDC aud + exact repo/Environment subject、限定ECR/ECS、用途別PassRole + PassedToService。管理者だけがbootstrap/CF実行roleを扱う | Environmentの許可ref・Actions侵害対策はGitHub設定。UpdateServiceはimage変更だけに制限できず、配備コードはapp/DDL権限を使える |
| DB/cache/APIへの直接侵入 | DataSubnetIdsにIGW直結なし、非公開RDS、用途別SG。public APIの受信はALB、8443はhookだけ、worker/migrationに受信許可なし | public taskはSG誤設定時に直接到達し得る。SG/routeの静的検査は実疎通、NACL、drift、TLS証明書の有効性を保証しない |
| Secretの混入/過剰共有 | 環境JSONは非秘密、OutputsはARNのみ、appとmigrationのexecution role/DBユーザーを分離。RDS masterは通常taskへ渡さない | DATABASE_URLのverify-full/CA、REDIS_URL TLS、Secret実値/rotationは管理者/Pipelineの未達ゲート。app Secret全体を共有する用途間の影響は残る |
| 非公開書類のCDN公開 | 画像/書類を別bucket、BPA/暗号化/OAC、画像だけにCloudFront Allow。書類は認可API+private/no-store | APIのListBucketは書類bucket全キーを列挙可能。workerには与えない。S3/認可実動作はアプリ/実AWS検証が必要 |
| 削除・置換/誤更新による消失 | RDS Snapshot/削除保護/backup/TLS、S3 versioning、永続資源のRetain/UpdateReplace、planの削除/置換拒否と終了保護 | Retainはbackup/復旧成功/無期限保存の保証ではない。SQS/log期限、version完全消去、復元/import、RPO/RTOは別検証 |
| 稼働revisionやcapacityの巻戻し | ECS/ALB/Schedulerを独立して取得し、未安定・権限不足・state変化で中止 | 分散ロックではない。全配備入口のpause排出と基盤管理者の単独操作が必要。AWS実行直前との競合窓は残る |
| 配備失敗や無通信障害の見逃し | hook、ECS alarm rollback、worker breaker、外部canary要求、SNS/Scheduler DLQ | `/health`はDB/外部API疎通を保証しない。疎なALB metricsの欠損を正常扱いするため外部canaryが必須。通知配送/非0 STOPPED監視は未検証・一部未実装 |

管理者CF実行roleには資源生成のための広い権限が残る。全wildcardを「AWS制約だから安全」とは扱わない。特にGlobalDeliveryAndBudgetはCloudFront/Budgetsの変更を `*` に許可するため、account内の他資源への影響が残る。専用account/管理者の短期認証・監査とChange Setの人的レビューが前提。実効権限/SCP/permissions boundaryは今回未確認。

## 設定と操作ツールの採否

| 対象 | 評価・判断 |
|---|---|
| environment JSON | 1つのrepo外非秘密入力を維持。必須入力と変更したい任意入力だけ。型/defaultはtemplate、既存省略値はlive Parameters。projectNameとresourcePrefixは用途が違うため一括改名しない |
| contracts.json | 操作が実際に読むregion/deploymentOrder/親間linksだけを残す。未使用version/stacks/nestedStacks/serviceRoles/outputInventoryと子間linksを削除。nested接続/Output定義はroot/templateが正本で、消費者のない全Output一覧を複製しない |
| environment.py | 手入力のOutputs/revisionやSecret拒否、live Outputsの必須接続、template制約の検査を維持。単なる転記をなくす利益が小さな専用resolverの保守を上回る。汎用schema/互換mapperは追加しない |
| runtime_state.py | CF旧Parametersはrelease後の実状態を表さないため維持。primary/production/testを別に読む。CLIの単純なDescribeStacksだけでは安全に置換できない |
| operations.py | native package/immutable Change Set/waiterを利用し、その前後のaccount・依存・state・置換検査だけを担当。手動AWS CLI案では検査漏れと再転記が増えるため採用しない。独自lock/自動復旧/Secret取得機能は追加しない |
| 検証入口 | `task infra:validate`→`run.sh check`、CIも`run.sh check`に統一。未使用の旧`cfn:check`/`validate.sh`を削除。install/lint/testは同じpinned環境を使う部分実行として残す |
| 文書 | 入口=全体像/正本、operations=操作、handoff=担当/接続、本文=採否、検証票=実環境の記録。コンテナ/queue/S3はアプリ固有契約だけ。過去の未実装表現とconfig cacheの矛盾を修正 |

## テスト群の棚卸し

削除ごとに代替を増やすのではなく、必要な保証だけを残す。CloudFormation schemaの検査と、schema-validでも危険な変更を拒否する検査を分ける。

| 群 | 防ぐ障害/検出範囲 | 重複・採否 | 限界 |
|---|---|---|---|
| cfn-lint 1.47.0 | 東京schema、型、intrinsic/Ref、template内部の誤接続 | 唯一のschema検査として維持。各Unit testで再lintしない | 実AWSのengine/quota/permissions、dynamic reference、driftは保証しない |
| test_contracts.py | 親links、起動Rules、全体の暗号化/保持/Secret混入、OIDC/PassRole、S3 SDK権限、CF/endpoint権限の契約 | network/OACの重複大テスト、全template在庫、role数固定を削除。親linksは実resolverが読むため残す | IAM simulatorではない。文字列/構造assertはpolicyの全経路を網羅しない。少数mutationは全脆弱性の証明ではない |
| test_foundation.py | nested接続、CIDR重複、data到達性、多AZ、SG、backup/TLS、Valkey認証、S3 version/OAC/TLS、redrive | NAT禁止/4subnet/route数固定、DB instance/MultiAZ/storage容量/HttpVersionの写し、汎用暗号化/保持の重複を削除（RDS固有のfinal Snapshotは維持）。data公開の拒否とNAT/追加subnetの許容を確認 | local Ref/Join/明示associationの現在のtemplate表現を対象。AZは式の比較であり東京の実提供や実経路解析ではない |
| test_runtime.py | 独立target条件、public taskのsubnet/public IPとhook/rollback/SG、release選択、alarm入力接続、draining/queue timeoutの層間契約 | CPU/memory/閾値defaultの写しを削除。初期0task、readonly、目的Output/role接続は消費者があるため維持 | hook/canaryコード・native deployment・本番imageの動作ではない。runtime定義の全IAM/commandを証明しない |
| test_environment.py | derived/secret/invalid/missing入力拒否、任意値維持、live接続、実アプリenv名 | resolverの公開挙動として維持 | 架空Outputs、外部ARNの実在/証明書SANやSecret keyは未確認 |
| test_runtime_state.py | revision/capacity/3target/Schedulerの保持、不安定/表現不能拒否 | 純粋変換に集中して維持。operationsと重なる正常fixtureは純粋変換とAWS呼出しの責務が別 | fake応答。SDK/AWSの実応答形との整合は実環境試験が必要 |
| test_operations.py | account、AccessDenied、exact Change Set、stale/置換/削除/競合、初期CREATE、native pagination/rollback、記録保護 | 副作用境界の振る舞いとして維持。安全ガードを削る根拠はない | FakeAwsは制御フローの証拠だけ。配備可能性/権限充足/復旧成功の証拠ではない |
| container verify/audit、PHPのqueue/S3 tests | imageの実起動・signal・内容と、アプリのSDK要求/認可を各境界で確認 | CF testsへ複製しない。今回image/PHPコード変更なしのため再build/全PHPテストは対象外 | DockerとSDK fakeの成功もFargate volume初期化やreal SQS/OACを保証しない |

## 変更影響・移行・切戻し

今回**CloudFormation YAML、logical/physical ID、IAM、ネットワーク、backup、アプリを変更しない**。AWSへの再配備・停止・データ移動は不要で、未知の既存環境の資源所属を変更しない。

- ローカル/外部CIで旧`cfn:check`または`validate.sh`を使う呼出しは`task infra:validate`または`bash scripts/cloudformation/run.sh check`へ変更する。repo内CI/文書は同時更新。
- contractsの削除項目はrepo内の操作コードで未使用。外部で在庫表として読んでいればtemplate Outputs/接続台帳へ切替える。release向けAWS Outputキー、environment形式、plan形式は変えない。互換alias/旧metadata読取りは残さない。
- 切戻しはこのPRのコード/入口/文書を戻すだけ。AWS変更もデータ書込もないためDB復元は不要。将来の構成変更はinventory→backup/移行/停止影響→Change Setレビュー→実試験→切戻し条件を別に承認する。

## 未達ゲートと保証できない事項

担当・証跡は[接続台帳](pipeline-handoff.md#未達ゲート担当)、実施条件は[検証票](validation-checklist.md)。通常運用可能という判定は行わない。

- Pipeline #156: immutable image/用途別revision・writable volume初期化、SSM/Secret/TLS/CA注入、purpose deployment判定、全入口pause排出、Cloudflare接続の実装・試験。
- 基盤管理者/実環境検証: 正しいaccountとinventory、engine/quota/資源実状態、hook ZIP/endpoint API/canary/SNS、DML/DDLユーザー、backup別資源復元/import、Valkey rotation、保持期限/RPO/RTO/費用。
- Scheduler: 現在は汎用1scheduleでDISABLED。月次/動画の2周期、producerのSendMessage権限（現雛形のWorkerTaskRoleは受信だけ）、非0 STOPPED監視、業務の重複抑止を準備するまで有効化しない。これは独立した機能/監視設計であり本PRで汎用枠を完成済みに見せない。
- 送金の外部成功/DB未保存境界は[queue契約](queue-operations.md)の運用開始阻害条件。インフラのstatic PASSやqueue timeoutでは解消しない。

採用したオフライン検証の今回の実測はPR本文へ記載し、今後の実環境結果はアクセス制限した検証票へ記録する。fake成功や文書の手順を実配備の成功へ読み替えない。
