# Wiki 操作履歴の接続元情報転送 v1

Backend #700 と frontend #440 の共通実装契約。対象は提出・承認・却下・取り下げ・公開・ロールバックの既存6操作。フロント #440 は未実装のため、両側の対応と鍵設定が揃うまで履歴の位置情報は null になる。

## 情報源と設定

Worker/BFF は操作ごとの Cloudflare メタデータ country / regionCode から値を生成する。ブラウザが渡した同名ヘッダー、body、画面言語用 x-kpool-country は情報源にしない。接続元の推定値であり住所・国籍ではない。生 IP は転送・保存しない。

バックエンドの `WIKI_VISITOR_LOCATION_SECRET` と Worker のサーバー専用 secret に、同じ32バイト以上のランダムな鍵の文字列を設定する。文字列の UTF-8 バイト列を HMAC 鍵として使い、base64 デコード等をしない。鍵は公開環境変数・ブラウザ・ログ・リポジトリに置かない。バックエンドの既定値は空文字で、32バイト未満も無効。実鍵の作成・配備は本実装に含まない。配備時は追加マイグレーションを適用し、両側の鍵を設定して config cache を更新する。鍵ローテーション時の一時的不一致は位置情報の未取得として扱う。

## ヘッダー

| ヘッダー | 値 |
| --- | --- |
| X-Kpool-Visitor-Country | 任意。ASCII 大文字2文字（例 JP, US）。Enum照合しない |
| X-Kpool-Visitor-Region | 任意。ASCII 大文字または数字1〜13文字（例 01, 13, CA）。国接頭辞を付けない |
| X-Kpool-Visitor-Timestamp | 署名時の Unix 秒。10桁の十進数字 |
| X-Kpool-Visitor-Signature | 下記 HMAC-SHA256 の小文字 hex 64文字 |

未取得値はヘッダーを省略する。空文字も未取得として扱う。国なしの地域は保存しない。地域の先頭ゼロを保持する。Enum 未登録でも形式が有効なコードは保持する。重複ヘッダーは受け付けない。空白の trim、大文字化、国接頭辞の削除等をバックエンドでは行わない。

## 署名対象の正確なバイト列

次の9要素を LF (`\n`, 0x0a) で連結し、末尾 LF は付けない。UTF-8 で符号化して HMAC-SHA256 を計算する。

1. 固定文字列 `kpool-visitor-v1`
2. Timestamp ヘッダーの値
3. バックエンド向け HTTP メソッド（大文字）
4. バックエンド向け request-target。先頭 `/` からの path と、存在する場合は `?` と query。スキーム・host・fragment は含めない。percent encoding、query 順序を変更しない
5. 最終的に転送する body のバイト列の SHA-256、小文字 hex
6. 最終的に転送する Authorization ヘッダーの値の SHA-256、小文字 hex。省略時は空文字の hash
7. 最終的に転送する Cookie ヘッダーの値の SHA-256、小文字 hex。省略時は空文字の hash
8. Country ヘッダーの値（省略時は空文字）
9. Region ヘッダーの値（省略時は空文字）

署名の後で JSON の再シリアライズ、Cookie の並べ替え、Authorization の書き換え、URI の書き換えをしない。転送後の実際の認証/session と本文に結び付けるため、別の利用者、別の操作、別の本文への署名流用は検証に失敗する。中間プロキシが値を変更すると位置情報は未取得になるので実配備経路で確認する。

Worker での計算例（Web Crypto、サーバー内でのみ実行）:

```ts
const encoder = new TextEncoder();
const hex = (bytes: ArrayBuffer) => Array.from(new Uint8Array(bytes), b => b.toString(16).padStart(2, "0")).join("");
const sha256 = async (bytes: Uint8Array) => hex(await crypto.subtle.digest("SHA-256", bytes));
const timestamp = Math.floor(Date.now() / 1000).toString();
// bodyBytes, authorization, cookie, requestTarget は転送する最終値。
const payload = [
  "kpool-visitor-v1", timestamp, method.toUpperCase(), requestTarget,
  await sha256(bodyBytes), await sha256(encoder.encode(authorization ?? "")),
  await sha256(encoder.encode(cookie ?? "")), country ?? "", region ?? "",
].join("\n");
const key = await crypto.subtle.importKey("raw", encoder.encode(secret), { name: "HMAC", hash: "SHA-256" }, false, ["sign"]);
const signature = hex(await crypto.subtle.sign("HMAC", key, encoder.encode(payload)));
```

## 検証と失敗時の扱い

バックエンドは現在時刻から過去300秒、未来30秒以内を許容する。署名は定数時間比較。timestamp、署名、値の形式、重複、鍵未設定のいずれかで検証に失敗したら国・地域とも null とする。署名が正しくても地域だけある場合は国・地域とも null。位置情報の問題で Wiki 操作を拒否しない。既存の認証・CSRF・認可・状態検証・Action のトランザクションはそのまま適用する。DB の履歴保存失敗は通常どおり操作を失敗・ロールバックさせる。

この署名は位置情報の出所を識別するもので、認証や操作の冪等性を提供しない。同一 session/body/URI/method に対する有効期間内の再送は位置情報検証として許容する。Wiki 操作の再送可否は既存の状態遷移と認可で判断する。署名の再利用による別 session / 別操作 / 別本文への差し替えは不可。

## frontend #440 の実装・配備確認

既存 BFF 5ルートの共通ヘッダー処理で Cloudflare の操作時メタデータを取得し、gateway が最終的に送る body・Cookie・Authorization・URI に対して署名する。Cloudflare 情報がない場合は受信位置情報ヘッダーを転送しない。現フロントに存在しない rollback ルートの追加は不要。バックエンドの rollback HTTP ルートも既存 #605 により無効化されており、本変更では有効化しない。RollbackWiki Action / UseCase の履歴伝搬は実装・テスト対象とする。将来ルートを有効化する場合は同じ契約を使う。

実配備で JP/01、US/CA、国のみ、取得不能、偽装されたブラウザヘッダー、別 session / URI / body への署名流用、期限切れを確認する。Workers fixture の成功と、Cloudflare 実メタデータ・AWS 経路での検証成功を区別する。バックエンドのユニットテスト成功だけで frontend #440 や実配備検証の完了とはしない。

## 相互運用テストベクトル

実鍵ではないテスト専用鍵 `test-only-visitor-forwarding-key-32-bytes`、timestamp `1800000000`、method `POST`、request-target `/api/v1/wiki/drafts/example/submit`、body `{"resourceType":"group"}`、Authorization `Bearer actor-a`、Cookie `session=actor-a`、country `JP`、region `01` の署名は以下になる。Node.js crypto で生成し、PHP verifier テストでも一致を確認する。通常の受信時には freshness 検証も必要。

```text
50e328bb74ff1624e557bca24c4454d7f508bd900ecdf56cf8a8d4a8d71d6725
```
