---
name: implement-issue
description: GitHub Issueを元にplanモードで計画を立て、implementerサブエージェントにworktreeで実装させ、結果を報告する。
disable-model-invocation: true
argument-hint: "<Issue番号>"
---

# GitHub Issue 実装スキル

あなたは GitHub Issue を元に設計・計画を行い、実装をサブエージェントに委譲するオーケストレーターです。
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

### Step 3: 作業ブランチの作成

Issue内容をもとに作業ブランチを作成してください。

**ブランチ名の命名規則**: `{Issue番号}-{Issue内容の要約}`
- 要約は英語のケバブケースで簡潔に（例: `265-add-account-identity-group-output-pattern`）

```bash
git checkout -b {Issue番号}-{Issue内容の要約}
```

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

## フェーズ3: サブエージェントによる実装

### Step 1: implementerサブエージェントに実装を委譲

`Agent` ツールを使い、`implementer` サブエージェントを **worktree隔離環境** で起動してください。

```
Agent({
  subagent_type: "implementer",
  isolation: "worktree",
  description: "Issue #{Issue番号} の実装",
  prompt: "以下の実装計画に従って実装してください。

## Issue情報
- Issue番号: #{Issue番号}
- タイトル: {Issueタイトル}

## 実装計画
{フェーズ2で策定した実装計画の全文}

## 参考実装
{調査で見つけた参考ファイルのパスと要点}
"
})
```

**重要**:
- `isolation: "worktree"` を必ず指定し、メインの作業ディレクトリに影響を与えないようにする
- 実装計画は省略せず、サブエージェントが自律的に作業できる十分な情報を渡す
- サブエージェントが参照すべき既存ファイルのパスを明記する

### Step 2: サブエージェントの結果を確認

サブエージェントから返された結果を確認してください：
- 実装内容のサマリー
- 作成/変更したファイル一覧
- Codexレビューの結果
- コミットハッシュ

worktreeで変更が行われた場合、worktreeのブランチ情報が返されます。

### Step 3: worktreeの変更を作業ブランチに統合

サブエージェントの実装が完了したら、worktreeブランチの変更を作業ブランチに取り込んでください。

```bash
git merge {worktreeブランチ名}
```

---

## フェーズ4: 結果報告

ユーザーに以下を報告してください：

- 実装内容のサマリー
- Codexレビューの結果（問題の有無）
- コミットハッシュ
- 次のステップの提案（PR作成など）

---

## 重要な原則

- **Issueの実装指示に従う**: Issue内の「AI向け実装指示」「実装ステップ」を最大限尊重する
- **計画はユーザー確認不要で即実行**: Issueに詳細が記載済みのため、Plan策定後はそのまま実装に進む
- **既存パターンを踏襲する**: 同じコンテキスト内の既存コードと一貫した実装をする
- **実装はサブエージェントに委譲**: worktree隔離環境で並列実行し、メイン環境を汚さない
- **受け入れ条件**: `make check` が通ること + Codexレビューで致命的な指摘がないこと