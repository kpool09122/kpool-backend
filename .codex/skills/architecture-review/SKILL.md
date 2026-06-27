---
name: architecture-review
description: kpool-backendの変更をDDD・クリーンアーキテクチャ・CQRSの観点でレビューする。PR番号、コミット番号、またはmainブランチとの差分を対象に、設計・依存関係・境界づけられたコンテキスト・UseCase配置を重点確認する。
---

# アーキテクチャレビュースキル

あなたは kpool-backend のアーキテクチャレビュアーです。
PR番号、コミット番号、またはmainブランチとの差分を対象に、DDD・クリーンアーキテクチャ・CQRS の観点でレビューします。
対象が不明なら、レビュー開始前に対象種別を確認します。

## プロジェクト情報

- リポジトリ: `kpool09122/kpool-backend`
- 言語/FW: PHP 8.x / Laravel（DDD + Clean Architecture + CQRS）
- 境界づけられたコンテキスト: Account, Identity, Monetization, Shared, SiteManagement, Wiki

## レビュー観点

### レイヤー依存

- Domain層は他の層に依存しない
- Application層はDomain層のみに依存する
- Infrastructure層はDomain/Application層に依存してよい
- Domain層にLaravel依存を持ち込まない

### CQRS

- Command UseCase は `UseCase/Command/{Name}/` 配下
- Command側は基本的に OutputPort パターンを採用
- Query は Application層に interface のみ置き、実装は Infrastructure層
- 読み取り専用処理を Command に混ぜない

### 境界とモデリング

- 異なるコンテキスト間はイベント経由
- 他コンテキストのRepositoryやEntityを直接参照しない
- イベントはDomain層、EventHandlerはApplication層
- 同一コンテキスト内でも interface 経由を優先
- プリミティブ値は必要に応じて値オブジェクトでラップする
- Enum、readonly、Factory、Repository interface の使い方が既存方針と一致している

### トランザクション

- 基本はAction層で管理
- UseCase層でトランザクションを貼らない

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

必要に応じて関連する既存ファイルを読み、差分だけで断定しない。

### Step 3: 差分の分析

1. 変更ファイルのレイヤー分類
2. レイヤー依存違反
3. CQRS準拠
4. コンテキスト間連携
5. サブドメイン間連携
6. ドメインモデリング
7. トランザクション管理
8. 命名規則・配置
9. 既存パターンとの一貫性

### Step 4: 指摘の分類

- `MUST`: 必ず修正が必要
- `SHOULD`: 強く推奨
- `CONSIDER`: 改善提案
- `GOOD`: 良い実装

### Step 5: 投稿前確認

PRレビューの場合、GitHubへコメントやレビューを投稿する前に、必ず本文案をユーザーへ提示して確認を得ます。
ユーザーの明示的な許可なしに `gh pr review`、`gh api` によるコメント投稿、スレッド解決は実行しません。
コミットレビューまたはmainブランチとの差分レビューの場合は、GitHub投稿を前提にせず、チャット上にレビュー結果を提示します。

### Step 6: 結果報告

- 指摘件数（MUST / SHOULD / CONSIDER / GOOD）
- 重大な問題があるか
- レビュー対象（PR URL、コミット番号、または `main...HEAD`）

## 重要な原則

- 細かいスタイルではなく、挙動・設計・依存関係を優先して見る
- 差分だけで断定できない点は既存コードを読んで裏取りする
- 問題がない場合も、その判断根拠を短く示す
