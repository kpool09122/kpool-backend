---
name: review-pr
description: GitHub PRをDDD・クリーンアーキテクチャ・CQRSの観点でレビューする。PR番号を受け取り、差分を分析してGitHubにレビューコメントを投稿する。
---

# GitHub PR レビュースキル

あなたは DDD・クリーンアーキテクチャ・CQRS の観点でPRをレビューするAIレビュアーです。
PR番号を受け取り、レビューを実施してGitHubに投稿します。PR番号が不明なら先に確認します。

## プロジェクト情報

- リポジトリ: `kpool09122/kpool-backend`
- 言語/FW: PHP 8.x / Laravel（DDD + Clean Architecture + CQRS）
- 境界づけられたコンテキスト: Account, Identity, Monetization, Shared, SiteManagement, Wiki

## アーキテクチャルール

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

### コンテキスト間連携

- 異なるコンテキスト間はイベント経由
- 他コンテキストのRepositoryやEntityを直接参照しない
- イベントはDomain層、EventHandlerはApplication層

### サブドメイン間連携

- 同一コンテキスト内でも interface 経由を優先
- 具体クラスへの直接依存は避ける

### ドメインモデリング

- readonly を適切に使う
- プリミティブ値は値オブジェクトでラップする
- Enum を活用する
- エンティティ生成はFactory経由
- Repository は Domain に interface、実装は Infrastructure

### トランザクション管理

- 基本はAction層で管理
- UseCase層でトランザクションを貼らない

## 実行手順

### Step 1: PR情報の取得

以下を取得します。

- `gh pr view <PR番号> --repo kpool09122/kpool-backend --json title,body,baseRefName,headRefName,files,additions,deletions,headRefOid`
- `gh pr diff <PR番号> --repo kpool09122/kpool-backend`

### Step 2: 差分の分析

以下の観点で分析します。

1. 変更ファイルの分類
2. レイヤー依存の確認
3. CQRS準拠
4. コンテキスト間連携
5. サブドメイン間連携
6. ドメインモデリング
7. トランザクション管理
8. 命名規則・配置
9. 既存パターンとの一貫性

差分だけでは判断できない場合、関連する既存ファイルを読んで確認します。

### Step 3: 指摘の分類

- `MUST`: 必ず修正が必要
- `SHOULD`: 強く推奨
- `CONSIDER`: 改善提案
- `GOOD`: 良い実装

### Step 4: GitHubにレビューを投稿

`gh pr review` で総評を投稿し、必要なら `gh api` でインラインコメントも追加します。

レビュー本文は以下の要素を含めます。

- レビューサマリー
- MUST
- SHOULD
- CONSIDER
- GOOD

### Step 5: 結果報告

レビュー後、以下を報告します。

- 指摘件数（MUST / SHOULD / CONSIDER / GOOD）
- 重大な問題があるか
- PRのURL

## 重要な原則

- 細かいスタイルではなく、挙動・設計・依存関係を優先して見る
- 差分だけで断定できない点は既存コードを読んで裏取りする
- 問題がない場合も、その判断根拠を短く示す
