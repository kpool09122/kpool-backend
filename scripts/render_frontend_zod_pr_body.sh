#!/usr/bin/env bash

set -euo pipefail

if [[ $# -ne 1 ]]; then
  echo "usage: $0 <output-file>" >&2
  exit 1
fi

OUTPUT_FILE="$1"

cat > "${OUTPUT_FILE}" <<'EOF'
## 📝 変更内容

バックエンドの最新 OpenAPI 定義から生成した Zod スキーマを更新し、フロントエンドで利用する依存関係を同期しました。

## 🏷️ 変更の種類

- [ ] 🚀 新機能 (Feature)
- [ ] 🐛 バグ修正 (Bug fix)
- [ ] 🔧 リファクタリング (Refactoring)
- [ ] 📚 ドキュメント (Documentation)
- [ ] 🧪 テスト (Tests)
- [x] 🔨 ビルド/CI (Build/CI)
- [ ] 💄 UI/UX (Style)
- [ ] ⚡ パフォーマンス (Performance)
- [ ] 🗑️ 削除 (Removal)

## 🎯 変更理由・背景

バックエンドの API 仕様とフロントエンドの Zod スキーマを手動実行 workflow で同期できるようにするためです。

## 🧪 テスト

### テストの実行確認

- [ ] `make check` を実行し、すべてのテストがパスすることを確認
- [ ] 新しく追加した機能に対するテストを作成
- [x] 既存のテストが壊れていないことを確認

### 動作確認

- `npm run lint`
- `npm run build`

## 🔍 レビューのポイント

- OpenAPI 由来の `src/types/*.ts` が API ごとに更新されていること
- `zod` と `@zodios/core` の依存追加または更新内容が妥当であること
- 生成ファイルの差分がバックエンドの最新 API 仕様と整合していること

## 📖 関連情報

### 関連Issue・タスク

Closes #該当なし
Fixes #該当なし
Relates to kpool09122/kpool-backend#146

## ⚠️ 注意事項

生成ファイルはバックエンドの OpenAPI をもとに自動生成しています。手修正が必要な場合は、先に生成元または生成処理を更新してください。


---

## チェックリスト

- [x] 自分でコードレビューを実施した
- [x] 適切なブランチ名を使用している
- [x] コミットメッセージが適切である
- [ ] 必要に応じてドキュメントを更新した
- [ ] 破壊的変更がある場合は適切に文書化した
EOF
