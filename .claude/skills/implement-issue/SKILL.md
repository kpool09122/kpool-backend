---
name: implement-issue
description: GitHub Issueを元にplanモードで計画を立て、実装し、Codex CLIでDDD/クリーンアーキテクチャ/CQRSのレビューを受けてからコミットする。
disable-model-invocation: true
argument-hint: "<Issue番号>"
---

# GitHub Issue 実装スキル

あなたは GitHub Issue を元に設計・実装・レビュー・コミットまでを一貫して行うエージェントです。
Issue番号 `$ARGUMENTS` を受け取り、以下のフェーズを順番に実行します。

**Issue番号が未指定の場合はユーザーに質問してください。**

---

## プロジェクト情報

- リポジトリ: `kpool09122/kpool-backend`
- 言語/FW: PHP 8.x / Laravel（DDD + Clean Architecture + CQRS）
- 境界づけられたコンテキスト: Account, Identity, Monetization, Shared, SiteManagement, Wiki
- ソースコード: `src/` 配下に各コンテキスト
- テスト: `tests/` 配下に各コンテキスト
- HTTP層: `application/Http/` 配下

---

## フェーズ1: Issue取得と理解

### Step 1: Issue情報の取得

```
gh issue view $ARGUMENTS --repo kpool09122/kpool-backend --json title,body,labels,assignees
```

### Step 2: Issue内容の確認

取得したIssue内容をユーザーに簡潔に提示してください：
- タイトル
- 概要（本文の要点）
- 対象コンテキスト
- 実装ステップ（Issueに記載があれば）

---

## フェーズ2: Planモードで実装計画を作成

### Step 1: Planモードに入る

`EnterPlanMode` ツールを使ってPlanモードに入ってください。

### Step 2: コードベースの調査

Issueの内容をもとに、以下を調査してください：

1. **対象コンテキストの既存構造を読む**: `src/{Context}/` 配下のディレクトリ構造とファイルを確認
2. **参考実装の確認**: Issueに記載がある場合は参考実装を読む。なければ同コンテキスト内の類似実装を探す
3. **影響範囲の調査**: 関連するファイル、テスト、ルート定義を確認
4. **既存パターンの把握**: 対象コンテキストでの命名規則、ディレクトリ構成、依存関係のパターンを理解

### Step 3: 実装計画の策定

調査結果をもとに、具体的な実装計画を策定してください：

- **作成/変更するファイルの一覧**: パスと変更内容を明記
- **実装順序**: 依存関係を考慮した実装順序
- **レイヤーごとの責務**: Domain → Application → Infrastructure → HTTP の各層で何を実装するか
- **テスト方針**: どのテストを追加/変更するか

### Step 4: 実装モードに切り替え

計画が策定できたら `ExitPlanMode` ツールで実装モードに切り替えてください。
Issueの段階で詳細が反映済みのため、**ユーザーへの計画確認は不要**です。そのまま実装に進んでください。

---

## フェーズ3: 実装

### Step 1: TDDで実装

**TDD（テスト駆動開発）で実装を進めてください。** 各機能について以下のサイクルを繰り返します：

1. **Red**: まずテストを書く（この時点ではテストが失敗する）
2. **Green**: テストが通る最小限の実装を書く
3. **Refactor**: コードを整理する

テスト実行は `make test`（全テスト）または `make test filter=TestClassName`（個別テスト）を使用してください。

#### 実装順序の目安

レイヤーごとに内側から外側へTDDで進めます：

1. **Domain層**（値オブジェクト → エンティティ → ドメインサービス）
   - テストを先に書き、ドメインロジックを実装
2. **Application層**（UseCase）
   - Repository等はモックしてテストを書き、UseCaseを実装
3. **Infrastructure層**（Repository実装、Query実装）
   - 必要に応じてDBを使うテスト（`@group useDb`）を書き、実装
4. **HTTP層**（Action、Request）
   - エンドポイントのテストを書き、Actionを実装

**実装時の注意事項**:
- **既存パターンを厳密に踏襲する**: 同じコンテキスト内の既存ファイルと同じスタイル・構造で実装
- **readonly classを使う**: 値オブジェクト、DTO、UseCaseは基本readonly
- **Domain層にフレームワーク依存を入れない**: Laravel固有クラスはInfrastructure層に閉じる
- **CQRS**: Command UseCaseはOutputPortパターンを採用、QueryはApplication層にinterfaceのみ
- **コンテキスト間連携はイベント経由**: 他コンテキストのRepositoryを直接呼ばない
- **値オブジェクトにバリデーション内包**: プリミティブ値は値オブジェクトでラップ
- **ファクトリパターン**: エンティティ生成はFactoryInterface経由

### Step 2: `make check` で受け入れ条件を確認

TDDサイクルが一通り完了したら、`make check`（cs-fix → phpstan → test）を実行してください。

```bash
make check
```

エラーがあれば修正し、`make check` が全てパスするまで繰り返してください。

---

## フェーズ4: Codexによるレビュー

### Step 1: Codex CLIでレビューを実行

`make check` がパスしたら、`codex review` コマンドでレビューを実行してください。

```bash
codex review --uncommitted "あなたはDDD・クリーンアーキテクチャ・CQRSの専門家です。以下の観点で変更内容をレビューしてください。細かいコードスタイルの指摘は不要です。致命的なエラーやアーキテクチャ違反のみを指摘してください。

## レビュー観点

1. **致命的なエラー**: 実行時にエラーになるコード、型の不一致、未定義の参照など
2. **レイヤー依存違反**: Domain層からApplication/Infrastructure層への依存、Application層からInfrastructure層への直接依存
3. **Domain層のフレームワーク依存**: Domain層でLaravel固有クラス(Eloquent, Facade等)を使用していないか
4. **CQRS違反**: Command/Queryの分離が適切か、Command UseCaseでOutputPortパターンを採用しているか
5. **コンテキスト間の直接依存**: 他コンテキストのRepositoryやEntityを直接参照していないか（イベント経由であるべき）
6. **サブドメイン間の直接依存**: 同一コンテキスト内のサブドメイン間でinterface経由でなく具体クラスを直接参照していないか
7. **トランザクション管理**: UseCase層でトランザクションを貼っていないか（Action層で管理すべき）

## レイヤー構造

src/{Context}/{Subdomain}/
├── Domain/          # ビジネスロジックの中核（他の層に依存しない）
├── Application/     # ユースケース層（Domain層のみに依存）
└── Infrastructure/  # 実装詳細（Domain/Application層に依存可）

application/Http/Action/ # HTTP層（UseCase呼び出しのみ）

## 出力形式

問題がある場合のみ、ファイルパスと行番号を含めて具体的に指摘してください。問題がなければ「致命的な問題はありません」と報告してください。"
```

### Step 2: レビュー結果の対応（修正→再レビューのループ）

致命的な指摘がなくなるまで以下を繰り返してください：

1. **指摘を修正する**
2. **`make check` を再実行して通ることを確認**
3. **`codex review --uncommitted` で再レビューを依頼する**

致命的な指摘がなくなったら、最後に `make check` を実行して通ることを確認してからフェーズ5に進みます。

各ラウンドのレビュー結果をユーザーに報告してください。

---

## フェーズ5: コミット

### Step 1: 変更内容の確認

```bash
git status
git diff --stat
```

### Step 2: コミット

変更ファイルをステージングし、コミットしてください。

**コミットメッセージのルール**:
- Issue番号を含める（`#{Issue番号}`）
- プレフィックス: `add:`, `fix:`, `refactor:`, `chore:` など
- 日本語で簡潔に内容を記載

```bash
git add <変更ファイル>
git commit -m "$(cat <<'EOF'
add: <変更内容の要約> (#<Issue番号>)

Co-Authored-By: Claude Opus 4.6 (1M context) <noreply@anthropic.com>
EOF
)"
```

### Step 3: 結果報告

コミット完了後、ユーザーに以下を報告してください：

- 実装内容のサマリー
- Codexレビューの結果（問題の有無）
- コミットハッシュ
- 次のステップの提案（PR作成など）

---

## 重要な原則

- **Issueの実装指示に従う**: Issue内の「AI向け実装指示」「実装ステップ」を最大限尊重する
- **計画はユーザー確認不要で即実行**: Issueに詳細が記載済みのため、Plan策定後はそのまま実装に進む
- **既存パターンを踏襲する**: 同じコンテキスト内の既存コードと一貫した実装をする
- **受け入れ条件**: `make check` が通ること + Codexレビューで致命的な指摘がないこと
- **レビューは修正→再レビューのループ**: 致命的な指摘がなくなるまで修正と再レビューを繰り返す
