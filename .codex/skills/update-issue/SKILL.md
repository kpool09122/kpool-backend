---
name: update-issue
description: 既存のGitHub Issueを取得し、要件ヒアリング→詳細分析→更新の3ステップでAIファーストなIssue品質に引き上げる。
---

# GitHub Issue 更新スキル

あなたは既存の GitHub Issue をAIファースト品質に引き上げるファシリテーターです。
以下の3フェーズを順番に実行し、各フェーズの完了時に要点を確認してから次に進みます。

## プロジェクト情報

- リポジトリ: `kpool09122/kpool-backend`
- GitHub Projects: `#1` (owner: `kpool09122`)
- 言語/FW: PHP / Laravel（DDD + Clean Architecture）
- 境界づけられたコンテキスト: Account, Identity, Monetization, Shared, SiteManagement, Wiki

## フェーズ1: 既存Issueの取得と要件ヒアリング

### Step 1: 既存Issueの取得

`gh issue view <Issue番号> --repo kpool09122/kpool-backend --json title,body,labels,assignees,milestone,projectItems`

### Step 2: 現状の分析

取得したIssueから以下を整理します。

- 現在のタイトル
- 現在の本文
- 既存ラベル
- AIファーストなIssueとして不足している情報

### Step 3: 差分ヒアリング

既存Issueから読み取れない不足分だけをユーザーに確認します。

- 何をしたいか
- 関係するコンテキスト
- 期待する振る舞い
- 受け入れ条件
- 必要に応じて関連Issue、依存関係、優先度、サイズ感

## フェーズ2: 詳細分析

コードベースを読んで以下を分析します。

1. 関連ファイルの特定
2. 既存設計パターンの確認
3. 影響範囲の調査
4. 実装方針の策定

分析結果を提示し、更新方針に問題がないか確認を取ります。

## フェーズ3: Issue更新

確認後、Issue本文を全体置換で更新します。

```markdown
## 概要

## 背景・動機

## AI向け実装指示

### 対象コンテキスト
- コンテキスト名: `src/{Context}/`

### 関連ファイル
- `src/...` - 変更理由
- `tests/...` - 変更理由

### 参考にすべき既存実装
- `src/...` - このパターンに倣う

### 実装ステップ
1. ...
2. ...
3. ...

### 設計上の制約・注意点
- ...

## 受け入れ条件
- [ ] ...
- [ ] ...

## テスト要件
- [ ] ...

## 補足情報
```

### Issue更新手順

1. `gh issue edit <Issue番号> --repo kpool09122/kpool-backend --body-file <tmpファイル>` で本文更新
2. 必要ならタイトルも更新
3. ラベルを追加・整理
4. Project 未追加なら追加
5. 更新したIssueのURLを報告

### ラベルルール

- コンテキスト: 対象コンテキスト名を小文字で付与
- タイプ:
  - `feature`
  - `bug`
  - `refactor`
  - `research`
  - `chore`

## 重要な原則

- 既存Issueの有用な情報は捨てずに引き継ぐ
- ファイルパスは実在するものだけ記載する
- 既存パターンを明示する
- 更新前後の差分が大きい場合は何を変えたか説明する
