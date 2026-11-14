# MCP 認可基盤の運用・接続契約

Issue #886 の基盤だけでは外部連携を公開しない。Passport の標準ルートは `Passport::ignoreRoutes()` で無効化し、同意省略の HTTP エンドポイントも追加しない。既存 `web` guard / `users` provider、Cookie、CSRF、セッション寿命、ログアウトを維持する。MCP トークンを既存 Cookie API の認証としてそのまま受け入れない。

## 設定と鍵

- `MCP_OAUTH_ISSUER`: 環境の認可サーバーの正規 issuer。
- `MCP_OAUTH_RESOURCE`: 同じ環境の MCP の正規 resource。Client ID ではない。
- `PASSPORT_PRIVATE_KEY` / `PASSPORT_PUBLIC_KEY`: 専用 RSA 鍵ペア。PEM または `\n` で改行を表した値をデプロイ先の秘密情報供給経路から注入する。未設定時は Passport 標準の `storage/oauth-private.key` / `storage/oauth-public.key` を参照する。秘密鍵を Git、コンテナイメージ、ログに含めない。
- 認可コードと Refresh Token の暗号化は Passport 標準のアプリケーション暗号化鍵に依存する。署名鍵や `APP_KEY` の交換は既発行トークンへの影響を伴うため、既存接続の失効・再同意を含めて計画する。

寿命は `config/mcp_oauth.php` で Access Token 3,600 秒、Refresh Token 2,592,000 秒（30日）、認可コード300秒とする。Access Token は毎時更新する外部連携向け、Refresh Token は継続接続と再同意のバランス、コードは短い単回交換期間を意図した採用値であり、内部 API トークンの60秒とは別である。Refresh Token は更新ごとにこの寿命で再発行され、旧 Refresh Token と対応する旧 Access Token は失効する。接続自体の絶対有効期限は設けず、接続の `revokedAt` を都度確認する。

## Client の事前登録

初期契約は Public Client、`authorization_code` + `refresh_token`、`token_endpoint_auth_method=none`、PKCE S256 必須。Confidential Client / client secret 認証はこの入口では利用しない。運営が実際のクライアントから確認した redirect URI を登録する。

```sh
task composer -- exec -- php artisan passport:client --public --name="ChatGPT" --redirect_uri="https://client.example/callback" --no-interaction
```

上記 URI は例であり実際の ChatGPT の URI を保証するものではない。認可時には登録文字列との完全一致を要求し、loopback URI のポートも例外にしない。コード交換でも認可コードに記録された URI との完全一致を要求する。Password / Implicit / Client Credentials / Personal Access などの grant は MCP の `exchange()` 入口で受け付けない。Passport 標準 HTTP ルートの再有効化で本実装を迂回しない。

## #887: 同意保存と認可コード発行

1. 既存ログインで Identity を認証し、元の Account / Principal、許可 Account / Delegation の現在の所属・代理権限・Policy を検証する。これは #887 の責務であり、本基盤の Scope は業務権限を付与しない。
2. サーバーで新しい UUID の接続 ID を採番し、`AuthorizationConnection` を組み立てる。Identity / Client、同意 Scope、許可対象、元の Account / Principal、許可集合内の初期対象、resource、同意日時を渡す。元 Account は許可集合に自動追加しない。代理先は Delegation ID 必須、本人 Account に Delegation ID を付けない。
3. `AuthorizationConsentServiceInterface::createConnection()` で永続化し、`issueCode($connectionIdentifier, AuthorizationCodeParameters)` で要求 Scope・Client・redirect URI・resource・S256 challenge・state を検証してクライアントへのリダイレクト URL を取得する。要求 Scope は同意 Scope の部分集合であり、別々に保持する。
4. 同意保存とコード発行を含む DB トランザクションは呼び出し元 Action で管理する。失敗時に保存だけを確定しない。`InvalidAuthorizationConsentException` はその HTTP 境界で変換する。

同一 Identity / Client の複数接続を許可する。接続・許可集合は挿入で保存し、同じ ID の再保存で上書きしない。後続の選択中 Account は接続の初期対象とは別で、#890 が管理する。

HTTP token endpoint の接続は後続で `McpAuthorizationService::exchange(ServerRequestInterface)` に委譲する。PSR-7 のパラメータ検証・OAuth エラーを扱う Infrastructure 境界であり、呼び出し元 Action が refresh rotation と旧トークン失効を含むトランザクションを管理する。エラー時の部分更新をコミットしない。

- 認可要求と `authorization_code` 交換では正規 `resource` が必須。欠落・空・別の値は `invalid_request`。
- `refresh_token` 交換では `resource` を省略でき、その場合も保存された利用先を維持する。指定時は正規値のみ許可する。
- コード交換に `scope` を指定して同意内容を変更することはできない。更新時の Scope 縮小は許可するが、縮小後の再拡大は拒否する。
- `connection_id` / `authorization_connection_id` / `identity_id` / `user_id` / `account_id` / `targets` の交換入力は拒否する。帰属・対象はサーバー側の対応から復元する。

## #888 / #889: 検証済み帰属の取得

`AuthorizationTokenVerifierInterface::verify($bearerToken)` は Passport / League の署名・時間・トークン失効検証を経由し、issuer、MCP resource の単一 audience、Identity、Client、Scope と保存されたトークンの一致、接続・Client の現在の失効状態を確認する。失敗は `InvalidAuthorizationTokenException`。検証前の claim を業務認可に利用しない。

戻り値 `ValidatedAuthorizationToken` は元 Access Token の ID、期限、付与 Scope、完全な `AuthorizationConnection` を返す。接続から Identity / Client / 接続 ID / resource / 許可対象 / 初期対象 / 元 Account・Principal / 同意日時・失効状態を取得できる。失効したトークン・接続は戻り値として返さない。

JWT は `iss` = 設定 issuer、`aud` = 設定 MCP resource、`sub` = Identity、`client_id` = Client、`jti` = 元 Access Token ID、`scopes` = 実付与 Scope。接続 ID や Account 許可集合は JWT に複製せず、検証済み `jti` → `oauth_access_tokens.authorization_connection_id` → 永続接続を参照する。認可コードも同じ接続 FK を持ち、Refresh Token は標準の `access_token_id` から元トークンと接続を追跡する。

#888 はこの検証契約から別の短命な内部 API 用トークンを発行する。#889 はその内部トークンの Bearer 認証とルート制限を接続する。本 Issue では内部トークンも業務 API の Bearer 許可も追加しない。各業務操作の現時点の Policy / 所属 / Delegation は #887 / #890 等で再検証する。

## #891 / #893: 失効と公開

`AuthorizationConnectionRepositoryInterface::revoke($identifier)` は最初の失効日時を保持する冪等な失効操作。以降のコード発行・コード交換・更新・検証で拒否する。#891 が外部連携一覧・解除 API、既存トークンへの連動、退会処理へ組み込む。

同意 API (#887)、内部トークンと API 認可 (#888 / #889)、Account 制限 (#890)、失効連動 (#891) を統合した後で、#893 の metadata / MCP エンドポイントを公開する。実際の ChatGPT 接続とフロント復帰は #893 / frontend#631 / frontend#632 の確認対象であり、本基盤のテスト成功を実接続成功とは扱わない。
