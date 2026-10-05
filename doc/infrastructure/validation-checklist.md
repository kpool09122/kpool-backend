# #672 本番統合・復元演習の検証票

関連: [主運用手順](operations.md)、[Pipeline引き渡し](pipeline-handoff.md)、[コンテナ](container-runtime.md)、[queue](queue-operations.md)、[S3](../../docs/s3-storage.md)。本票は#671の文書レビュー成果物であり、**以下の実環境検証はすべて未実施**。チェックを埋めるのは#672担当者で、静的検証の合格を実環境成功へ置き換えない。

## 証跡と判定の保存先

実施前に#672のIssue本文へ、アクセス制限した環境台帳／証跡保管庫のURL、owner、保持期限を登録する。具体的URLは#157／#672で未確定。秘密値を含まない要約だけをIssueに記録し、元ログ・スクリーンショット・変更前後JSONはマスキングして保管庫の `<環境>/<変更IDまたはrelease ID>/<試験ID>/` に保存する。Secret値、認証token、Cookie、個人情報、queue payload全体は保管しない。

各試験は以下を記録する。FAIL／BLOCKED／未実施をPASSにしない。危険な失敗注入は承認した検証環境・データで実施し、本番を破壊して権限境界を試さない。

```text
試験ID／担当／日時（UTC）／環境／AWS account・region／Cloudflare account:
status: 未実施 | PASS | FAIL | BLOCKED
事前条件・復旧責任者・中止閾値:
backend SHA / frontend SHA / workflow SHA / release ID:
image digest / task definition ARN / ECS deployment ID / Worker version:
入力（秘密値なし）・実行コマンド／Console操作・exitCode:
変更前状態／期待結果／観測結果／所要時間／RPO・RTO:
証跡保管庫URL（アクセス制限）:
未達・制約・修正担当Issue・次回確認方法:
文書反映: operations.md節 / pipeline-handoff.md行 / PR URL
```

## 実環境チェックリスト

| ID | 前提・操作 | 合格条件／証跡 | 担当・依存 |
| --- | --- | --- | --- |
| C01 | 短期認証、STS account／東京、package bucket、既存OIDC確認 | 正しいaccount・権限、長期AWSキー不使用、認証期限とCLI version | #157／#672 |
| C02 | bootstrap→package済みroot→integration→runtimeのCREATE | 4親とroot子のstatus、Outputs接続、子の重複なし、4親終了保護・ALB／RDS保護 | #157／#672 |
| C03 | 初期0 task・Scheduler DISABLED、Secret未設定時ゲート | 雛形を起動しない。hook／canary／Secret不足を#156が変更前に拒否 | #156／#672 |
| C04 | 承認した入力／権限／DNS失敗を検証環境で再現 | CREATE失敗の原因追跡、データ保持と再構築判断、失敗を完了と扱わない | #157／#672 |
| C05 | UPDATE／rollback失敗とcontinue手順 | Change Set・nested review、停止維持、skip無し復旧を優先、skip時は不整合解消と再照合 | #157／#672 |
| D01 | 固定両SHAと同一digestで初回migration→API→worker→frontend | migration exit=0、API hook／bake／対象deployment成功、worker処理、全体疎通。旧API無しの制約記録 | #156／#672 |
| D02 | 2回目Blue/Green、APIで約90秒処理中に配備・停止 | 100%切替と5分bake後旧task停止、120秒draining、切断／5xx／signal時刻を記録 | #156／#672／#669 |
| D03 | hook失敗・timeout・TLS／期待revision不一致 | 切替前は旧API維持、Pipeline失敗、worker／frontend停止 | #157／#156／#672 |
| D04 | bake中にalarm発報、canary停止／計測欠損も確認 | 旧APIへrollback、対象deploymentを失敗記録、後続停止。低トラフィックでも検知 | #157／#156／#672 |
| D05 | 完了後の障害・初回失敗・workerのみ失敗 | 正常revision手動復旧、初回後続停止と修正再実行、worker部分成功と対象復旧、DBを自動downgradeしない | #156／#672 |
| D06 | backend成功／frontend失敗、応答途絶後の再確認 | 新backend／旧frontendを記録、同じSHA／成果物でfrontendだけ再実行、migration重複無し | #156／#672 |
| D07 | all／backend／frontend、検証・build・migration失敗 | 対象外変更なし、allは両build成功後backend→frontend、失敗時後続停止 | #156／#672 |
| S01 | 全配備入口disable、queued／waiting／実行中run・単発task排出 | 他入口／ローカル操作も停止、concurrencyだけに依存しない、失敗時disable維持 | #156／#157／#672 |
| S02 | 2回目反転後のdrift同期、再読取・Change Set・UPDATE | ARN／desired、Primary／Production／Testの実対応と重み、Schedulerが維持される。古いJSONで0化／巻戻し無し | #157／#672 |
| S03 | 中間重み／進行中bake／未知target、更新失敗を確認 | 更新中止、停止維持、修復後照合してからenable、既知drift=0を要求しない | #157／#672 |
| R01 | RDS PITRとsnapshotを別DBへ復元 | TLS／権限／schema／件数／業務整合、RPO/RTO、旧DB保持、書込停止と新endpoint切替、切戻し条件 | #157／#672 |
| R02 | S3 delete markerと過去versionの復元 | DBキーと内容一致、画像invalidation完了＋GET、非公開書類は認可APIのみ | #157／#672 |
| R03 | Valkey snapshotを別cacheへ復元 | TLS／user／Secret、session再ログイン、challenge／lock・副作用照合、新endpoint・CF管理の方針 | #157／#672 |
| R04 | Secret更新と新task反映・Valkey password移行 | 旧新task混在とAPP_KEY互換、config cache、user/Secret同期、旧password失効の順を実証 | #157／#672 |
| Q01 | SQS受信／例外／timeout／kill／ローリング停止 | 90<120<300、再配送、failed_jobsとwork DLQを区別、冪等性・送金外部成功境界の解消確認 | #157／#672 |
| Q02 | DLQ／failed_jobsの個別再処理 | 業務ID／外部副作用照合、速度制限、二重再送無し、再発監視 | #157／#672 |
| Q03 | 月次／動画の2schedule・CLI・STOPPED非0とdelivery失敗 | 2周期とcommand／timezone、初期DISABLED、Scheduler・ECS終了・jobの3段階監視。汎用1枠のまま有効化しない | #157／#672 |
| A01 | 許可外repo／Environment OIDC、他role PassRole、CF／IAM変更拒否 | policy検証・安全な拒否試験、ActionsはALB／CF／Secret値を管理しない、残余データアクセスリスク記録 | #156／#157／#672 |
| A02 | S3画像／書類、他bucket／prefix、未署名GET、権限差 | OAC画像公開、書類はno-store認可ストリーム、書類がCDNに出ない | #672、S3文書 |
| F01 | Workers build／preview／deploy、bindingsとtoken最小権限 | 選定方式・固定SHA・version・設定場所記録、不足設定を変更前に拒否 | frontend／#157／#156／#672 |
| F02 | private reusable workflow・App ref解決／checkout・Secret渡し | 正しいrepo／SHA、Contents: read限定、backend Environment、frontendへ必要Secretだけ | #156／#672 |
| F03 | 東京API／us-east-1画像証明書、NS／DNS-only／proxy条件 | TLS／SAN／更新レコード、API Host、画像URL、cache条件 | #157／#672 |
| F04 | Cookie／CSRF／CORS／OAuth／passkey／ログイン／Wiki表示 | 正常と拒否、Secure／SameSite・exact Origin・RP ID・callback、Workers経由でも認可維持 | #672／frontend |
| M01 | Budgets金額／80%実績／100%予測・email、canary／SNS通知 | 対象account全体、配送証跡、予測不足・遅延制約、Cloudflare／外部料金別管理 | #157／#672 |
| M02 | #147負荷とAPI／worker・RDS・Valkey増強 | 実測・見積日と前提・旧新重複費用、releaseサイズ反映・drift同期、切戻し試験 | #147／#157／#672 |
| M03 | ログ／snapshot／ECR／S3旧version／Retain廃止判断 | 保存期限・owner承認、復旧対象digest保護、個人情報消去、残留課金inventory | #157／#672 |

## #671 文書レビューの机上シナリオ

1. 初期値: API/worker=0、release ARN未指定、Blue／Blue／Green、Scheduler DISABLED。CREATE後、終了保護とOutputs、hook／canary／SecretとDNS、#156初回配備の前提を順に確認する。未準備なら0タスクのまま中止、汎用Schedulerを有効化しない。
2. 稼働後: 正常release ARN、desired=1、Blue/Green反転後の例を読む。全配備排出→独立した3target値を同期→Change Setで巻戻し拒否→更新直前再読取→適用後照合→再開。中間重みと更新失敗なら停止維持。
3. 部分成功: 新API／旧workerまたは新backend／旧frontend。実ARN／両SHAを記録し対象だけ復旧／再実行、DBは別扱い。#156に対象選択が無ければ未達ゲートとして戻す。
4. 復元: 別DB／別cache、書込停止前後で切戻し可否が異なる。元DB／cacheのCF Outputsが復元先へ自動追従しないことと、所有関係再管理が別ゲートであることを確認する。

#672でPASSになっても制約が残る場合、結果要約・証跡URL・担当Issueを本票へ追記し、実際に修正した操作をoperations.md／pipeline-handoff.mdへ反映する。未達は担当Issueと再試験方法を残し、#157の完了判定へ引き渡す。
