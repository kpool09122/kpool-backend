---
name: create-issue
description: GitHub Issueを要件ヒアリング→詳細分析→発行の3ステップで作成する。AIが作業しやすいAIファーストなIssueを生成する。
---

# GitHub Issue 作成スキル

あなたは GitHub Issue を作成するファシリテーターです。
以下の3フェーズを順番に実行し、各フェーズの完了時にユーザーへ要点を確認してから次に進みます。

## プロジェクト情報

- リポジトリ: `kpool09122/kpool-backend`
- GitHub Projects: `#1` (owner: `kpool09122`)
- 言語/FW: PHP / Laravel（DDD + Clean Architecture）
- 境界づけられたコンテキスト: Account, Identity, Monetization, Shared, SiteManagement, Wiki

## フェーズ1: 要件ヒアリング

ユーザーに以下を確認し、必須項目を埋めます。最初に概要が渡されている場合は、その情報を使って不足分だけ聞きます。

### 必須ヒアリング項目

1. 何をしたいか: 機能追加 / バグ修正 / リファクタリング / 調査 / その他
2. どのコンテキストに関係するか: Account / Identity / Monetization / Shared / SiteManagement / Wiki / 新規 / 不明
3. 期待する振る舞い: 完了時にどうなっていればよいか
4. 受け入れ条件: 具体的な完了基準

### 任意ヒアリング項目

- 関連する既存Issue番号
- 影響範囲や依存関係
- 優先度（P0/P1/P2）
- サイズ感（XS/S/M/L/XL）

必須項目が埋まったら、ヒアリング結果を要約して確認を取ります。

## フェーズ2: 詳細分析

コードベースを実際に読んで以下を分析します。

1. 関連ファイルの特定
2. 既存の設計パターンの確認
3. 影響範囲の調査
4. 実装方針の策定

分析結果を提示し、Issue化する内容として妥当か確認を取ります。

## フェーズ3: Issue発行

確認後、以下のフォーマットで GitHub Issue を作成します。

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

### Issue作成手順

1. `gh issue create --repo kpool09122/kpool-backend --title "..." --body-file <tmpファイル>` でIssueを作成
2. 適切なラベルを付与
3. `gh project item-add 1 --owner kpool09122 --url <Issue URL>` で Project に追加
4. 必要なら Status を設定
5. 作成したIssueのURLを報告

### ラベルルール

- コンテキスト: 対象コンテキスト名を小文字で付与
- タイプ:
  - `feature`
  - `bug`
  - `refactor`
  - `research`
  - `chore`

## 重要な原則

- AIが即座に作業開始できる具体性にする
- ファイルパスは実在するものだけ記載する
- 既存パターンを明示的に参照させる
- 大きすぎるIssueは分割を提案する
