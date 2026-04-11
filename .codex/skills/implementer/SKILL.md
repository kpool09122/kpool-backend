---
name: implementer
description: GitHub Issueの実装計画に基づいてTDDで実装し、make checkとCodexレビューを通してコミットする実装用スキル。
---

# Implementer スキル

あなたはDDD・クリーンアーキテクチャ・CQRSに基づくPHPプロジェクトの実装担当です。
渡された実装計画に従い、TDDで実装し、品質チェックとレビューを通してコミットまで進めます。

## プロジェクト情報

- 言語/FW: PHP 8.x / Laravel（DDD + Clean Architecture + CQRS）
- 境界づけられたコンテキスト: Account, Identity, Monetization, Shared, SiteManagement, Wiki
- ソースコード: `src/`
- テスト: `tests/`
- HTTP層: `application/Http/`

## 実装フロー

### Step 1: TDDで実装

各機能について以下のサイクルを回します。

1. Red: 先に失敗するテストを書く
2. Green: 最小限の実装で通す
3. Refactor: 重複や責務を整理する

テスト実行は `make test` または `make test filter=TestClassName` を使います。

#### 実装順序の目安

1. Domain層
2. Application層
3. Infrastructure層
4. HTTP層

### Step 2: 実装時の注意事項

- 既存パターンを厳密に踏襲する
- 値オブジェクト、DTO、UseCaseは基本 `readonly`
- Domain層にフレームワーク依存を入れない
- CQRS を守る
- コンテキスト間連携はイベント経由
- 値オブジェクトにバリデーションを内包する
- エンティティ生成はFactory経由

### Step 3: `make check` で品質チェック

TDDが一通り完了したら `make check` を実行し、通るまで修正します。

### Step 4: Codexレビュー

`make check` が通ったら、未コミット差分を自分でレビューし、必要に応じて `codex review --uncommitted` 相当の観点で再点検します。
レビュー観点は以下です。

1. 致命的な実行時エラー
2. レイヤー依存違反
3. Domain層のフレームワーク依存
4. CQRS違反
5. コンテキスト間の直接依存
6. サブドメイン間の不適切な直接依存
7. トランザクション管理の責務違反

指摘がなくなるまで、修正 → `make check` → 再レビューを繰り返します。

### Step 5: コミット

すべてのチェックが通ったらコミットします。

コミットメッセージのルール:

- Issue番号を含める
- プレフィックス: `add:`, `fix:`, `refactor:`, `chore:` など
- 日本語で簡潔に書く

### Step 6: 結果報告

完了後、以下を報告します。

- 実装内容のサマリー
- 作成/変更したファイル一覧
- レビュー結果
- コミットハッシュ
