# ブランチ保護ルールの設定方法

このドキュメントでは、GitHubでブランチ保護ルールを設定して、CIパイプラインが通るまでmainブランチへのマージを禁止する方法を説明します。

## 設定手順

### 1. GitHubリポジトリの設定画面にアクセス

1. GitHubリポジトリのページに移動
2. `Settings` タブをクリック
3. 左サイドバーの `Branches` をクリック

### 2. ブランチ保護ルールを追加

1. `Add rule` ボタンをクリック
2. `Branch name pattern` に `main` と入力
3. 以下の設定を有効にする：

#### 必須の設定

- ✅ **Require a pull request before merging**
  - `Require approvals` を有効にし、必要な承認者数を設定（推奨：1以上）
  - `Dismiss stale PR approvals when new commits are pushed` を有効にする

- ✅ **Require status checks to pass before merging**
  - `Require branches to be up to date before merging` を有効にする
  - `Status checks that are required` で以下を選択：
    - `test` (CIワークフローのジョブ名)

#### 推奨の設定

- ✅ **Require conversation resolution before merging**
- ✅ **Require signed commits**
- ✅ **Require linear history**
- ✅ **Include administrators**

### 3. 設定を保存

1. 設定が完了したら `Create` ボタンをクリック
2. 設定が適用されるまで数分待つ

## 設定後の動作

### 機能ブランチからmainへのマージ時

1. プルリクエストを作成
2. CIパイプラインが自動実行される
3. **CIパイプラインが成功するまでマージボタンが無効化される**
4. CIが成功したら、承認者による承認が必要
5. 承認後、マージが可能になる

### 保護される内容

- ❌ CIが失敗している状態でのマージ
- ❌ 承認者による承認なしでのマージ
- ❌ 古いコミットでのマージ（mainブランチが最新でない場合）
- ❌ 未解決の会話がある状態でのマージ

## トラブルシューティング

### CIが失敗する場合

1. ローカルで `make check` を実行して問題を確認
2. コードスタイルの問題を修正
3. 静的解析の問題を修正
4. テストが通るように修正
5. 修正後、再度プッシュしてCIを実行

### 緊急時の対応

緊急でマージが必要な場合：

1. リポジトリ管理者に連絡
2. 一時的にブランチ保護ルールを無効化
3. マージ完了後、保護ルールを再び有効化

## 注意事項

- ブランチ保護ルールは一度設定すると、すべてのユーザー（管理者を含む）に適用されます
- 設定変更は慎重に行い、チーム内で共有してください
- CIパイプラインの設定とブランチ保護ルールの設定は連携して動作します 