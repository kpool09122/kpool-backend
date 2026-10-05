# インフラ整理の設計判断と移行

## 棚卸しと採用理由

| 観点 | 従来の課題 | 採用した構成・理由 |
|---|---|---|
| 親stack | 4親間のOutputsをJSONへ転記 | 4親の責務を維持し、contracts.linksから実Outputsを導出。データ保持・信頼bootstrap・computeを巨大な単一stackへ混ぜない |
| runtime更新 | 稼働revision/desired/3target/schedulerを手動同期 | ECS/ALB/Schedulerを正本とするstate resolver。独立したproduction/test/primaryを読み、未安定なら拒否。計画中・直前・完了後に確認 |
| 設定 | 複数の架空Parameter例とtemplate/contractに同じ定義 | 外部の1環境JSON、制約とdefaultはtemplate、接続とOutputキーはcontract。省略時は既存Parameterを維持 |
| 名前 | ProjectNameとResourcePrefixの一括統一は置換リスク | 接頭辞の責務を明示し既存物理名を保持。bootstrap IAMへの基盤名/queue/hook/zone転記は導出 |
| 非秘密SSM | IMAGE_BUCKET/FILE_BUCKETはアプリで未使用 | AWS_PUBLIC_IMAGES_BUCKET/AWS_PRIVATE_FILES_BUCKET等、現行config/queue/filesystemsのenv名へ整合。ImageBaseUrlもrootから接続 |
| 操作 | 長い注意書き、UsePreviousValueの誤用 | plan/executeで同じ入力/実状態からimmutable Change Setを作成。account違い・missing output・AccessDenied・stale plan・置換/削除をfail closed |
| 検証 | infra:validate/cfn:checkのPython/依存経路が別 | pinned run.shを唯一の実装、infra:validateを通常入口、CIも同じshellを実行 |
| 文書 | 実装履歴、作成/同期操作の繰り返し | infra READMEから運用・接続・専門資料へ。操作はoperationsが正本。検証票は変更リスク選択型 |

## セキュリティ見直し

変更したのは運用入力/接続/実行手順。実AWSの安全性が確認できないため、用途別IAM/SG、OIDC、暗号化、非公開書類、保持とbackupの境界を緩めない。
手動コピーを削除し、生成されたARNに限定したrole指定とaccount照合を採用。Secret値を扱うAPIを操作ツールに持たせない。
resource数やNATの全面禁止は安全性そのものではないため、固定構成を守るテストを削除。private data、限定ingress、書類をCDNに出さないこと、用途別Secret/PassRole、保持、失敗時中止は残す。
残るリスクは管理者の強い変更権限、Actions侵害時のアプリ/DDLコード実行、pause違反actorとの競合、外部hook/canary/rotationの実装・運用品質。スクリプトは分散排他の代わりではない。

## 既存環境の確認結果と移行

実装時に利用可能なAWS資格情報でSTSは応答したが、k-poolの配備先と確認できず、東京CloudFormation ListStacksとECS ListClustersはidentity policyの明示的Deny。
**稼働環境の有無・資源状態は未確認**。未配備と推測して改名・置換・削除は行っていない。AWS/Cloudflareへの変更操作もしていない。
管理者は正しいk-pool profile/accountを用意し、4親と物理ID・engine version・現revision・backup・DNSのinventoryを環境台帳へ記録する。AccessDeniedをnot found扱いしない。

移行手順:

1. 全配備を排出し、既存stack名/projectName/resourcePrefix/package bucketをそのまま環境JSONへ登録。運用記録を保管する。
2. 必須外部入力と任意入力の変更希望をinputsへ設定。稼働stateとOutputsは転記しない。既存のカスタムqueue等の省略値は現在Parameterから保持する。
3. bootstrap→必要な親の順にplan。削除/置換/命名変更を拒否し、nestedもレビューする。実行権限不足・不安定なら中止。
4. integrationにImageBaseUrl入力が加わり、SSM JSONのIMAGE_BUCKET/FILE_BUCKETを実アプリenv名へ変更する。SSMを旧キーで読む配備側consumerがあれば、同時に新キーへ修正する。読み取りfallbackは追加しない。SSM物理名/Output ARN/Secret保存先は変更しない。
5. runtime planが実revision/desired/転送先/Schedulerを維持することを確認し、実行後に比較。アプリsmoke/監視を確認後pause解除。

この変更のtemplate差分はintegrationの非秘密設定だけで、DB/VPC/ALB等のlogical IDやphysical namingを変えない。
新環境なら同じ入口で4親を順にCREATEし、0task/DISABLEDのまま外部成果物を準備する。初回アプリ配備自体は本リファクタリングの完了条件ではない。

切戻し: AWSに適用していない段階はコード/環境入力を戻すだけ。integration適用後はSSM consumerを先に照合し、旧templateへ戻すChange Setを別にレビューする。
runtimeを含む切戻しも過去Parameter JSONを再利用せず、現stateを再取得する。接続変更・新DB書込後のデータ切戻しは通常コードrollbackと分ける。

## テストの棚卸し・検証の限界

削除: 全Parameterを例に複製する2テスト、foundationリソース数/親一覧を固定するテスト、runtime雛形の重複テスト、架空例全8ファイル、contractsのexternalInputs/sharedParameters重複。
維持: cfn-lintのAWS schema、nested/link整合、private network、OAC、IAM/Secret分離、保持/暗号化、起動Rules、release選択、drainingとqueue timeoutの意味ある契約。
追加: 設定の誤り/禁止入力、live Outputs取込み、非秘密env名、現在state保持と不安定拒否、account/権限不足、planの同一性/変化、置換・削除拒否、private記録。
AWS応答のfixtureは**テスト用**で、実環境を成功と報告するための代替データではない。

静的検証とCLIのオフライン実行はPRのテスト結果に記載。AWS change set/create/update、IAM実権限、native BLUE_GREENの実応答、復旧/競合、Cloudflareは未実施。
確認方法と変更別の選択は検証票参照。実環境の保証は行わない。

## Pipeline/フロントの対応範囲

関連リリースPipeline（Issue #156）は未実装であることをlive Issue/PR確認済み。AWS outputsのキーと4親名は維持する。
必要な対応は新SSM env名の採用、TLS/CA付きDATABASE_URLとValkey TLS URLの安全な注入、基盤作業と全配備入口の排他、目的deploymentの判定。
フロントのAPI URL/Cloudflare account/token/bindingsと認証originは既存接続台帳のまま。この変更によるfrontendコード/ドメイン変更はない。
外部hook/canary、業務固有Scheduler、復元資源のimport/rotationは稼働前に確認する事項であり、未実装/未検証を本PRで実証済みとしない。
