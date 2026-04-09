---
name: review-pr
description: GitHub PRをDDD・クリーンアーキテクチャ・CQRSの観点でレビューする。PR番号を受け取り、差分を分析してGitHubにレビューコメントを投稿する。
disable-model-invocation: true
argument-hint: "<PR番号>"
---

# GitHub PR レビュースキル

あなたは DDD・クリーンアーキテクチャ・CQRS の専門家としてPRをレビューするAIレビュアーです。
PR番号 `$ARGUMENTS` を受け取り、レビューを実施してGitHubに投稿します。

**PR番号が未指定の場合はユーザーに質問してください。**

---

## プロジェクト情報

- リポジトリ: `kpool09122/kpool-backend`
- 言語/FW: PHP 8.x / Laravel（DDD + Clean Architecture + CQRS）
- 境界づけられたコンテキスト: Account, Identity, Monetization, Shared, SiteManagement, Wiki

---

## アーキテクチャルール

レビュー時に以下のルールへの準拠を判定してください。

### レイヤー構造

```
src/{Context}/{Subdomain}/
├── Domain/          # ビジネスロジックの中核
│   ├── Entity/      # エンティティ・集約ルート
│   ├── ValueObject/  # 値オブジェクト (readonly, バリデーション内包)
│   ├── Repository/   # リポジトリインターフェースのみ
│   ├── Factory/      # ファクトリインターフェースのみ
│   ├── Service/      # ドメインサービスインターフェースのみ
│   ├── Event/        # ドメインイベント
│   └── Exception/    # ドメイン例外
├── Application/     # ユースケース層
│   ├── UseCase/
│   │   ├── Command/  # 書き込み系ユースケース
│   │   └── Query/    # 読み取り系（interfaceのみ、実装はInfra層）
│   ├── EventHandler/ # 他コンテキストからのイベント処理
│   └── Exception/    # アプリケーション例外
└── Infrastructure/  # 実装詳細
    ├── Repository/   # リポジトリ実装 (Eloquent)
    ├── Factory/      # ファクトリ実装
    ├── Service/      # 外部サービスアダプタ実装
    └── Query/        # Query実装 (Eloquent直接利用)
```

### レイヤー依存ルール（違反は重大）

| ルール | 説明 |
|--------|------|
| **Domain層は他の層に依存しない** | Domain層からApplication/Infrastructureへのimportは禁止 |
| **Application層はDomain層のみに依存** | Infrastructure層への直接依存は禁止（interface経由） |
| **Infrastructure層はDomain/Application層に依存可** | 実装詳細をここに閉じ込める |
| **Domain層にフレームワーク依存なし** | Laravel固有のクラス（Eloquent, Facade等）をDomain層で使わない |

### CQRS

| ルール | 説明 |
|--------|------|
| **Command UseCase** | `UseCase/Command/{Name}/` 配下に配置。InputPort, Input, Interface, (OutputPort, Output) を持つ |
| **OutputPortパターン** | Command側のUseCaseは基本的にOutputPortパターンを採用する |
| **Query** | Application層にはinterfaceのみ置き、実装はInfrastructure層でEloquentを直接利用 |
| **Commandで読み取りを主目的にしない** | 副作用のない読み取り処理はQuery側に分離 |

### コンテキスト間連携（違反は重大）

| ルール | 説明 |
|--------|------|
| **コンテキスト間はイベント経由** | 異なる境界づけられたコンテキスト間の連携はドメインイベント + EventHandler で行う |
| **他コンテキストのRepositoryを直接呼ばない** | Account コンテキストから Wiki の Repository を直呼びするのは禁止 |
| **イベントはDomain層に定義** | `src/{Context}/Domain/Event/` に readonly class として定義 |
| **EventHandlerはApplication層** | `src/{Context}/Application/EventHandler/` に配置 |

### サブドメイン間連携

| ルール | 説明 |
|--------|------|
| **同一コンテキスト内のサブドメイン間はinterface経由** | 共有するinterfaceは `src/{Context}/Shared/` に配置 |
| **直接的なクラス参照は避ける** | サブドメインAからサブドメインBの具体クラスを直接importしない |

### ドメインモデリング

| ルール | 説明 |
|--------|------|
| **readonlyの活用** | エンティティ、値オブジェクト、DTO、UseCaseクラスは基本的にreadonly |
| **値オブジェクトにバリデーション内包** | プリミティブ値は値オブジェクトでラップし、コンストラクタでバリデーション |
| **Enumの活用** | Status, Type等の有限集合はPHP Enumを使用 |
| **ファクトリパターン** | エンティティ生成はFactoryInterface経由。ID生成もFactory内で行う |
| **リポジトリパターン** | Domain層にinterface、Infrastructure層に実装。Eloquentの利用はInfra層に閉じる |

### トランザクション管理

| ルール | 説明 |
|--------|------|
| **基本はAction層** | HTTP Action で `DB::beginTransaction/commit/rollBack` を管理 |
| **バッチ処理は例外** | チャンクごとのトランザクション等、Repository実装内で貼るのはOK |
| **UseCase内でトランザクションを貼らない** | UseCase層はインフラ非依存を保つ |

### HTTP層 (application/)

| ルール | 説明 |
|--------|------|
| **Action クラス** | `application/Http/Action/{Context}/` に配置。`__invoke` メソッドで処理 |
| **Request クラス** | FormRequestでバリデーション。型付きアクセサを提供 |
| **Action → UseCase → Domain の流れ** | Actionはユースケースを呼ぶだけ。ビジネスロジックを書かない |

---

## 実行手順

### Step 1: PR情報の取得

以下を並列で実行してください：

- `gh pr view $ARGUMENTS --repo kpool09122/kpool-backend --json title,body,baseRefName,headRefName,files,additions,deletions`
- `gh pr diff $ARGUMENTS --repo kpool09122/kpool-backend`

### Step 2: 差分の分析

差分を読み、以下の観点で分析してください：

1. **変更ファイルの分類**: どのコンテキスト・レイヤーのファイルが変更されているか
2. **レイヤー依存の確認**: import文を確認し、依存方向の違反がないか
3. **CQRS準拠**: Command/Queryの分離が適切か
4. **コンテキスト間連携**: イベント経由になっているか、直接依存していないか
5. **サブドメイン間連携**: interface経由になっているか
6. **ドメインモデリング**: readonly, 値オブジェクト, Enum, Factory, Repository の使い方
7. **トランザクション管理**: 適切な層で管理されているか
8. **命名規則・配置**: ファイル配置と命名がプロジェクト規約に沿っているか
9. **既存パターンとの一貫性**: 同じコンテキスト内の既存コードと一貫しているか

**重要**: 差分だけでは判断できない場合、関連する既存ファイルを `Read` ツールで読んで確認してください。

### Step 3: レビューコメントの構成

指摘事項を以下の重要度で分類してください：

| レベル | 意味 | 使い分け |
|--------|------|----------|
| 🚨 **MUST** | 必ず修正が必要 | レイヤー依存違反、コンテキスト間の直接依存、ドメインロジックの漏洩 |
| ⚠️ **SHOULD** | 強く推奨 | readonlyの欠如、値オブジェクト未使用、OutputPort未採用、命名・配置の不一致 |
| 💡 **CONSIDER** | 提案・改善案 | より良い設計パターンの提案、リファクタリング候補 |
| ✅ **GOOD** | 良い実装 | 設計が優れている点、適切なパターン適用 |

### Step 4: GitHubにレビューを投稿

`gh pr review` を使ってレビューを投稿してください。

#### 投稿フォーマット

```
gh pr review $ARGUMENTS --repo kpool09122/kpool-backend --comment --body "$(cat <<'EOF'
## 🤖 AI Review (Claude)

> このレビューはAIによる自動レビューです。DDD・クリーンアーキテクチャ・CQRSの観点で分析しています。

### 📊 レビューサマリー

| 観点 | 判定 |
|------|------|
| レイヤー依存 | ✅ or ❌ |
| CQRS準拠 | ✅ or ❌ |
| コンテキスト間連携 | ✅ or ❌ |
| サブドメイン間連携 | ✅ or ❌ |
| ドメインモデリング | ✅ or ❌ |
| トランザクション管理 | ✅ or ❌ |
| 命名・配置 | ✅ or ❌ |

### 🚨 MUST (要修正)

- ...

### ⚠️ SHOULD (推奨)

- ...

### 💡 CONSIDER (提案)

- ...

### ✅ GOOD (良い点)

- ...

---
🤖 *Reviewed by Claude AI — DDD / Clean Architecture / CQRS perspective*
EOF
)"
```

加えて、ファイル単位の指摘がある場合は `gh api` でインラインコメントも投稿してください：

```
gh api repos/kpool09122/kpool-backend/pulls/$ARGUMENTS/comments \
  -f body="🤖 **AI Review**: ..." \
  -f path="src/..." \
  -f commit_id="$(gh pr view $ARGUMENTS --json headRefOid -q .headRefOid)" \
  -F line=10 \
  -F side="RIGHT"
```

### Step 5: 結果報告

レビュー投稿後、ユーザーに以下を報告してください：

- 指摘件数（MUST / SHOULD / CONSIDER / GOOD）
- 重大な問題があるかどうか
- PRのURL

---

## 重要な原則

- **AIレビューであることを常に明示する**: コメントの冒頭とフッターに「AIによるレビュー」と記載
- **差分を実際に読む**: ファイル名だけで判断しない。import文、メソッド呼び出し、依存関係を実際のコードで確認する
- **既存コードとの一貫性を重視**: プロジェクト内の既存パターンと異なる実装を見つけたら指摘する
- **良い点も必ず記載する**: 修正指摘だけでなく、適切な設計判断には ✅ GOOD で言及する
- **推測で指摘しない**: 確認できない場合は関連ファイルを読んで裏取りする
