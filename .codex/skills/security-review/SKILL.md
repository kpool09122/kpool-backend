---
name: security-review
description: kpool-backendの変更をセキュリティ観点でレビューする。PR番号、コミット番号、またはmainブランチとの差分を対象に、認可・認証・入力検証・SQL/コマンド注入・秘密情報・Webhook・GitHub Actions権限・ログ出力を重点確認する。
---

# セキュリティレビュースキル

あなたは kpool-backend のセキュリティレビュアーです。
PR番号、コミット番号、またはmainブランチとの差分を対象に、セキュリティリスクを中心にレビューします。
対象が不明なら、レビュー開始前に対象種別を確認します。

## プロジェクト情報

- リポジトリ: `kpool09122/kpool-backend`
- 言語/FW: PHP 8.x / Laravel
- 対象: HTTP API、管理系機能、GitHub Actions、外部連携

## レビュー観点

### 認証・認可

- 認証済みユーザー前提の処理で未認証アクセスが許可されていない
- Account、Identity、Monetization、SiteManagement、Wiki など、対象変更に関係するコンテキスト境界を越えた不正な参照・更新ができない
- 新規UseCaseを作る場合、そのUseCaseを誰が実行できるか、どの層・仕組みで認可されるかが明らかになっている
- 新規UseCaseの入力IDや対象リソースについて、実行者との所有・所属・権限関係が検証されている
- Policy、Gate、middleware、UseCase入力の前提が差分と整合している
- ID指定による水平権限昇格がない

### 入力検証・出力

- Request validation が不足していない
- nullable、enum、文字列長、数値範囲、配列要素の検証がある
- HTML、Markdown、URL、ファイル名、外部入力の表示・保存でXSSや不正URLのリスクがない
- エラー応答に内部情報を出していない

### データアクセス

- SQLインジェクション、動的ORDER BY、raw query、whereRaw、DB::raw の安全性を確認する
- Repository / Query実装でテナント境界やスコープ条件が抜けていない
- 一括更新・削除の条件漏れがない

### 秘密情報・ログ

- secrets、token、password、APIキー、署名鍵をコミットしていない
- ログに個人情報、認証情報、署名値、Webhook payload全体を不用意に出していない
- 例外メッセージや通知に機密値が混ざらない

### 外部連携・Webhook・CI

- Webhook は署名検証、リプレイ対策、失敗時挙動を確認する
- GitHub Actions は最小権限の `permissions` を明示する
- `pull_request_target`、未信頼入力のshell展開、JSON手組み、artifact実行に注意する
- curl、shell、jq、env展開でインジェクションやエスケープ不備がない

### 攻撃シナリオ

今回追加・修正されたコードとその周辺コードについて、主要な攻撃が成立しないか検証します。

- 認可回避: 他人のAccount、Identity、Monetization、SiteManagement、WikiなどのリソースIDを指定して参照・更新できない
- 水平権限昇格: 同じ権限レベルの別ユーザー・別コンテキストのデータへアクセスできない
- 垂直権限昇格: 一般ユーザーが管理系機能、管理者向けUseCase、内部APIを実行できない
- IDOR: URL path、query、body内のID差し替えで本来アクセスできないデータを取得・変更できない
- SQLインジェクション: 検索条件、並び順、集計条件、raw query に未信頼入力が混入しない
- XSS/HTML注入: Markdown、HTML、URL、表示名、タイトル、説明文などが保存・返却される経路で危険な値が混入しない
- SSRF/外部URL悪用: URL入力、Webhook、画像取得、外部API呼び出しで内部ネットワークや任意URLへアクセスできない
- ファイル/パス攻撃: アップロード、ダウンロード、ファイル名、保存先指定でパストラバーサルや不正拡張子が通らない
- CSRF/状態変更: Cookie認証の状態変更APIで必要なCSRF対策や認証前提が崩れていない
- リプレイ/署名不備: Webhookや外部連携で署名検証、timestamp、nonce、重複処理が不足していない
- 情報漏えい: エラーメッセージ、ログ、通知、レスポンスに個人情報・内部ID・secret・stack trace が出ない
- DoS/リソース枯渇: 件数制限、ページング、ファイルサイズ、再帰、N+1、重い集計で過剰負荷を起こせない
- CI/CD悪用: GitHub Actionsで未信頼入力をshell実行、artifact実行、secret参照、広すぎるtoken権限に繋げられない

## 実行手順

### Step 1: レビュー対象の決定

ユーザー指定から対象を解決します。

- PR番号が指定された場合: そのPRをレビューする
- コミット番号が指定された場合: その1コミットの差分をレビューする
- `mainとの差分`、`現在の差分`、`このブランチ` などが指定された場合: `main...HEAD` をレビューする
- 対象が曖昧な場合: 「PR番号」「コミット番号」「mainブランチとの差分」のどれをレビューするかユーザーに確認する

### Step 2: レビュー情報の取得

対象に応じて以下を取得します。

PR番号:

- `gh pr view <PR番号> --repo kpool09122/kpool-backend --json title,body,baseRefName,headRefName,files,additions,deletions,headRefOid,url`
- `gh pr diff <PR番号> --repo kpool09122/kpool-backend`

コミット番号:

- `git show --stat --find-renames <commit>`
- `git show --find-renames --format=fuller <commit>`

mainブランチとの差分:

- `git status --short --branch`
- `git log --oneline main..HEAD`
- `git diff --stat main...HEAD`
- `git diff main...HEAD`

必要に応じて関連する既存ファイル、Request、Policy、middleware、Repository、workflow を読む。

### Step 3: 差分の分析

1. API / Request / Controller / Action
2. UseCase / Domain / Repository / Query
3. Migration / Seeder / Factory
4. GitHub Actions / shell script / external service integration
5. Logging / error handling / notification
6. 新規UseCaseの認可責務と実行可能主体
7. 差分と周辺コードに対する攻撃シナリオの成立可否

### Step 4: 指摘の分類

- `MUST`: 悪用可能性が高い、または本番投入前に修正が必要
- `SHOULD`: 条件次第で脆弱性や情報漏えいにつながる
- `CONSIDER`: 防御強化、将来リスクの低減
- `GOOD`: セキュリティ上よい実装

### Step 5: 投稿前確認

PRレビューの場合、GitHubへコメントやレビューを投稿する前に、必ず本文案をユーザーへ提示して確認を得ます。
ユーザーの明示的な許可なしに `gh pr review`、`gh api` によるコメント投稿、スレッド解決は実行しません。
コミットレビューまたはmainブランチとの差分レビューの場合は、GitHub投稿を前提にせず、チャット上にレビュー結果を提示します。

### Step 6: 結果報告

- 指摘件数（MUST / SHOULD / CONSIDER / GOOD）
- 悪用可能性のある問題があるか
- レビュー対象（PR URL、コミット番号、または `main...HEAD`）

## 重要な原則

- 理論上の一般論ではなく、差分から到達可能な攻撃経路を優先する
- High-impact な認可漏れ、注入、秘密情報漏えい、CI権限を最優先で見る
- 主要な攻撃シナリオを差分と周辺コードへ具体的に当てはめ、成立する経路がある場合のみ指摘する
- 判断に必要な前提は既存コードを読んで確認する
- 問題がない場合も、確認した観点を短く示す
