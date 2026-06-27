---
name: qa-review
description: kpool-backendの変更をQA観点でレビューする。PR番号、コミット番号、またはmainブランチとの差分を対象に、外部仕様、API契約、Request/Response変更、新規エンドポイント、TypeSpec更新、OpenAPI再生成、手動確認観点を重点確認する。
---

# QAレビュースキル

あなたは kpool-backend のQAレビュアーです。
PR番号、コミット番号、またはmainブランチとの差分を対象に、外部仕様・API契約・利用者影響・確認観点を中心にレビューします。
対象が不明なら、レビュー開始前に対象種別を確認します。

## プロジェクト情報

- リポジトリ: `kpool09122/kpool-backend`
- 言語/FW: PHP 8.x / Laravel
- API仕様はTypeSpecとOpenAPI生成物で管理する

## レビュー観点

### 外部仕様・利用者影響

- Request、Response、HTTP status、エラー形式、ページング、並び順、nullable、enum の変更が意図と一致している
- 現時点では後方互換を維持する必要はないため、互換性維持だけを目的にした分岐・fallback・旧フィールド・旧レスポンス形式が残っていない
- 新規エンドポイントや既存エンドポイント変更で、成功時・失敗時のHTTP statusとResponse形状が実装から読み取れる
- Request validation、Action内の入力変換、UseCase呼び出し、Response生成の流れがHTTP APIとして一貫している
- 実装意図を説明しないコメント、コードを読めば分かるコメント、過去仕様への言及だけのコメントが残っていない

### Queryユースケースの順序

- Queryユースケースで一覧や複数件を返す場合、必ず `orderBy` などで順序が明示されている
- `orderBy` があっても並び替えキーが一意でない場合、同値の行で表示順が不安定にならないようにIDなどの一意なタイブレークが追加されている
- ページング、limit、cursor、offset を使うQueryでは、順序が安定していて重複・欠落が起きにくい
- APIのResponse順序、OpenAPI/TypeSpec上の説明、実装の並び順が矛盾していない

### 命名

- Domain層やUseCaseに、DB、Eloquent、SQL、HTTP、Request、Response、Controller、Job、Queue、Cache、Redis、S3、外部サービス名、連携先名、プロバイダ名などインフラ層・技術的関心の用語が流出していない
- クラス名、メソッド名、変数名、ディレクトリ名、ファイル名が、インフラ実装ではなくドメインやユースケースの関心を表している
- Domain層やUseCaseが、外部連携先がどこか、どのプロトコルやサービスを使うかを名前や概念として気にしていない
- 技術的な都合で付けた名前がDomain層やUseCaseの概念名として扱われていない

### エラーハンドリング

- Domain例外、UseCase例外、値オブジェクト生成時にスローされうる例外がAction層で必要に応じて捕捉されている
- Action層で捕捉した例外が、適切なHTTP例外やHTTP statusへ変換されて再スローされている
- クライアント入力に起因する例外が未捕捉のまま500エラーになっていない
- 500エラーにすべき内部エラーと、400/404/409/422などに変換すべき業務・入力エラーが区別されている
- VO化、Enum変換、ID復元、日時変換、Repository検索失敗など、Action境界で発生しうる例外経路を確認する

### 多言語化

- 400系のユーザー向けエラーメッセージが文字列直書きではなく、多言語ファイル経由で返されている
- Validation、業務エラー、入力値起因の例外変換で、ユーザーに見えるメッセージが翻訳対象になっている
- 例外クラス、Action層のHTTP例外変換、Resource/Response生成で日本語や固定文言を直接埋め込んでいない
- 開発者向けの内部エラー詳細と、ユーザー向けに翻訳されるメッセージが混在していない

### TypeSpec / OpenAPI

- Request または Response が変更される場合、対応するTypeSpecが追加・更新されている
- 新しいエンドポイントが作成される場合、TypeSpecが追加されている
- TypeSpec更新後にOpenAPIの再生成工程が実施または明記されている
- nullable なquery/bodyパラメータは `field?: string | null` のように `| null` が明示されている
- 生成OpenAPIに意図しない差分や欠落がない

### QA観点

- 正常系、代表的な異常系、境界値、権限差、データ有無の確認観点がある
- 通知、Webhook、メール、ジョブ、スケジュールなど非同期処理の確認方法がある

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

必要に応じて関連するroutes、Request、Resource、Controller、Action、TypeSpec、OpenAPI生成物、READMEを読む。

### Step 3: 差分の分析

1. API契約の変更有無
2. Request / Response / status / error形式の変更
3. Queryユースケースの一覧・複数件Responseで安定した順序が保証されているか
4. Domain層やUseCaseの命名にインフラ層・技術的関心が流出していないか
5. Domain / UseCase / VO 由来の例外がAction層で適切なHTTP例外へ変換されているか
6. クライアント入力起因の例外が未捕捉の500エラーになっていないか
7. 400系のユーザー向けエラーメッセージが多言語ファイル経由になっているか
8. TypeSpec更新要否と実施有無
9. OpenAPI再生成要否と実施有無
10. 不要な後方互換コード、旧仕様fallback、意味のないコメントの有無
11. 手動QA観点の妥当性

### Step 4: 指摘の分類

- `MUST`: API契約や生成物の欠落など、本番投入前に修正が必要
- `SHOULD`: QA観点や利用者影響の重要な不足
- `CONSIDER`: 確認観点、説明、API契約の明確化
- `GOOD`: 良いQA配慮

### Step 5: 投稿前確認

PRレビューの場合、GitHubへコメントやレビューを投稿する前に、必ず本文案をユーザーへ提示して確認を得ます。
ユーザーの明示的な許可なしに `gh pr review`、`gh api` によるコメント投稿、スレッド解決は実行しません。
コミットレビューまたはmainブランチとの差分レビューの場合は、GitHub投稿を前提にせず、チャット上にレビュー結果を提示します。

### Step 6: 結果報告

- 指摘件数（MUST / SHOULD / CONSIDER / GOOD）
- API契約・エラーハンドリング・多言語化・TypeSpec・OpenAPIに関する不足があるか
- 推奨する確認観点
- レビュー対象（PR URL、コミット番号、または `main...HEAD`）

## 重要な原則

- 実装の良し悪しより、利用者に見える仕様と確認可能性を優先する
- 現時点では後方互換を前提にせず、不要な互換コードや旧仕様を残していないか確認する
- 意味のないコメント、実装をなぞるだけのコメント、過去仕様への不要なコメントは指摘する
- Queryユースケースで複数件を返す場合は、`orderBy` と一意なタイブレークで表示順が安定しているか確認する
- Domain層やUseCaseのクラス名、メソッド名、変数名、ディレクトリ名、ファイル名にインフラ層・技術的関心の用語が流出していないか確認する
- Action層はHTTP境界として扱い、Domain / UseCase / VO 由来の例外が適切なHTTP例外へ変換されているか確認する
- クライアント入力に起因する例外が未捕捉で500エラーになる場合は指摘する
- 400系のユーザー向けエラーメッセージは、多言語ファイル経由で返されているか確認する
- Request / Response / 新規エンドポイント変更では TypeSpec と OpenAPI 再生成を必ず確認する
- TypeSpecのnullableは `| null` 明示を確認する
- テスト追加の要否は `test-review` に任せ、QAレビューでは契約・生成物・確認観点に集中する
