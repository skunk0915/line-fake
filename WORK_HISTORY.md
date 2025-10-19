# 作業履歴

## 2025-10-19: 初回実装完了

### 実装した機能
1. ✅ プロジェクト構造の設計と基本ファイル作成
2. ✅ フロントエンド実装（HTML/CSS/JavaScript）
3. ✅ LINE風UIの実装
4. ✅ PWA対応（manifest.json、service-worker.js）
5. ✅ バックエンドAPI実装（PHP）
6. ✅ 会員登録・ログイン機能
7. ✅ メッセージ送受信機能
8. ✅ データベース設計とSQL作成
9. ✅ README.mdとデプロイ手順作成

### 作成したファイル一覧

#### フロントエンド
- `index.html` - メインHTMLファイル（ログイン、登録、チャット画面）
- `css/style.css` - LINE風のスタイルシート
- `js/app.js` - フロントエンドロジック（認証、メッセージ送受信）
- `manifest.json` - PWAマニフェスト
- `service-worker.js` - Service Worker（オフライン対応）

#### バックエンド（PHP API）
- `api/config.php` - データベース設定と共通関数
- `api/config.example.php` - 設定ファイルのサンプル
- `api/register.php` - 会員登録API
- `api/login.php` - ログインAPI
- `api/users.php` - ユーザー一覧取得API
- `api/messages.php` - メッセージ取得API
- `api/send_message.php` - メッセージ送信API

#### データベース
- `database/schema.sql` - データベーススキーマ（users、messagesテーブル）

#### 設定・ドキュメント
- `package.json` - プロジェクト設定
- `.gitignore` - Git除外設定
- `.htaccess` - さくらサーバー用設定
- `README.md` - プロジェクト説明とデプロイ手順
- `images/icon.svg` - PWAアイコンのSVGテンプレート
- `images/README.md` - アイコン生成手順

### 技術仕様
- **フロントエンド**: Vanilla JavaScript（ライブラリ不要）
- **バックエンド**: PHP 7.4+
- **データベース**: MySQL 5.7+
- **認証**: 簡易JWT実装
- **リアルタイム性**: 3秒ごとのポーリング

### セキュリティ対策
- パスワードのbcryptハッシュ化
- プリペアドステートメントによるSQLインジェクション対策
- JWT認証
- XSS対策（入力検証）
- HTTPS必須（PWA要件）

### 次のステップ（オプション）
- [ ] PWAアイコン画像の生成（icon-192.png、icon-512.png）
- [ ] WebSocketによるリアルタイムメッセージング
- [ ] 既読機能の追加
- [ ] 画像・ファイル送信機能
- [ ] グループチャット機能
- [ ] プッシュ通知
- [ ] レート制限の実装
- [ ] ユニットテストの追加

### エラー・修正履歴
- エラーなし（初回実装）

## 2025-10-19: エラー修正（アイコン404エラーと認証エラー）

### 発生したエラー
1. **アイコン404エラー**:
   - `images/icon-192.png` が見つからない
   - `manifest.json` で PNG画像を参照していたが、実際には `icon.svg` しか存在しなかった

2. **API認証エラー (401)**:
   - `api/users.php` へのリクエストが認証エラーを返す
   - ローカルストレージに保存されたトークンが無効または期限切れの可能性

### 実施した修正
1. **manifest.json の修正**:
   - PNG画像参照から SVG画像参照に変更
   - `icon-192.png`, `icon-512.png` → `icon.svg` (sizes: "any")

2. **js/app.js の認証エラーハンドリング改善**:
   - `apiCall()` 関数に401エラー時の自動ログアウト処理を追加
   - 認証エラー時にローカルストレージをクリアしてログインページに遷移
   - `loadChatList()` のエラーハンドリングにコメント追加

### 編集したファイル
- `/Users/mizy/Dropbox/line-fake/manifest.json` - アイコン設定を SVG に変更
- `/Users/mizy/Dropbox/line-fake/js/app.js` - 認証エラーハンドリングを改善

## 2025-10-19: 新規登録後の認証エラー修正

### 発生したエラー
- **新規登録/ログイン後の401エラー**:
  - 登録後やログイン後に `users.php` へのアクセスで401エラーが発生
  - 初期化コードで古いトークンを使用して API にアクセスしていた
  - 登録が「このメールアドレスは既に登録されています」で失敗した場合でも `loadChatList()` が呼ばれていた

### 実施した修正
1. **初期化処理の改善**:
   - 同期的な初期化を非同期関数 `init()` に変更
   - トークン検証時のエラーハンドリングを追加
   - `loadChatList()` を await で待機するように修正

2. **デバッグログの追加**:
   - 登録/ログイン成功時のログ出力
   - ユーザー情報保存確認のログ
   - エラー時の詳細ログ

### 編集したファイル
- `/Users/mizy/Dropbox/line-fake/js/app.js` - 初期化処理とログイン/登録処理の改善

## 2025-10-19: JWT実装のバグ修正

### 発生したエラー
- **ログイン成功後の401エラー**:
  - ログインは成功してトークンも保存されるが、次の `users.php` へのアクセスで401エラー
  - トークンの検証が失敗していた

### 原因
1. **Base64エンコーディングの問題**:
   - JWT仕様では Base64 URL-safe エンコーディングが必要だが、標準の `base64_encode()` を使用していた
   - 標準のBase64には `+` や `/` などの記号が含まれ、HTTPヘッダーで問題を引き起こす

2. **hash_hmac()の出力形式の問題**:
   - `hash_hmac()` のデフォルト出力は16進数文字列
   - 第4引数に `true` を指定してバイナリ出力を取得する必要があった

### 実施した修正
1. **Base64 URL-safe エンコーディング関数の追加**:
   - `base64UrlEncode()` - `+/` を `-_` に変換し、末尾の `=` を削除
   - `base64UrlDecode()` - `-_` を `+/` に戻してデコード

2. **JWT生成・検証の修正**:
   - `generateToken()` - Base64 URL-safe エンコーディングを使用
   - `verifyToken()` - Base64 URL-safe デコーディングを使用
   - `hash_hmac()` に第4引数 `true` を追加してバイナリ出力を取得

3. **デバッグログの追加**:
   - `requireAuth()` にAuthorizationヘッダーとトークンの検証結果をログ出力

### 編集したファイル
- `/Users/mizy/Dropbox/line-fake/api/config.php` - JWT実装の修正とデバッグログ追加

## 2025-10-19: デバッグ機能の追加

### 追加した機能
1. **フロントエンドのデバッグログ強化**:
   - API呼び出し時にAuthorizationヘッダーとトークンをコンソールに出力
   - エラー応答の詳細情報を表示

2. **サーバー側デバッグツール**:
   - `api/test_token.php` - トークンの生成と検証をテストするスクリプト
   - `api/debug_auth.php` - 認証リクエストの詳細をデバッグするエンドポイント

### 作成したファイル
- `/Users/mizy/Dropbox/line-fake/api/test_token.php` - トークンテストスクリプト
- `/Users/mizy/Dropbox/line-fake/api/debug_auth.php` - 認証デバッグエンドポイント

### 編集したファイル
- `/Users/mizy/Dropbox/line-fake/js/app.js` - デバッグログの追加

## 2025-10-19: Authorization ヘッダー取得の改善

### 実施した修正
1. **getallheaders()の代替実装を追加**:
   - 一部のサーバー環境で `getallheaders()` が存在しない場合に備えて、$_SERVER から直接ヘッダーを取得する代替実装を追加

2. **requireAuth()関数の改善**:
   - Authorizationヘッダーを4つの異なる方法で取得を試みる
   - 大文字・小文字の違いに対応
   - Apache環境のREDIRECT_HTTP_AUTHORIZATIONにも対応
   - デバッグ情報をエラーレスポンスに含めるように改善

3. **.htaccessファイルの作成**:
   - Authorizationヘッダーが正しくPHPに渡されるようにRewriteRuleを設定
   - 開発環境用のエラー表示設定を追加

### 作成したファイル
- `/Users/mizy/Dropbox/line-fake/.htaccess` - Authorizationヘッダー設定とエラー表示設定

### 編集したファイル
- `/Users/mizy/Dropbox/line-fake/api/config.php` - getallheaders()代替実装とrequireAuth()改善

## 2025-10-19: メッセージ送受信の表示バグ修正

### 発生した問題
- **メッセージの送受信が逆に表示される**:
  - 相手のメッセージが自分のメッセージとして表示される
  - 自分のメッセージが相手のメッセージとして表示される

### 原因
- **データ型の不一致**:
  - PHPのPDOはデータベースから取得した数値を文字列として返すことがある
  - JavaScriptで `message.sender_id === currentUser.id` の厳密等価演算子で比較していた
  - 片方が文字列、もう片方が数値の場合、比較が常に false になる

### 実施した修正
1. **フロントエンド (js/app.js)**:
   - `createMessageElement()` で `Number()` を使用して型を統一して比較
   - デバッグログを追加して型情報を出力

2. **バックエンド API の型変換**:
   - `api/messages.php` - メッセージのIDを整数型に変換
   - `api/users.php` - ユーザーIDを整数型に変換
   - `api/login.php` - ユーザーIDを整数型に変換
   - `api/register.php` - ユーザーIDを整数型に変換
   - `api/send_message.php` - メッセージIDを整数型に変換

3. **デバッグログの追加**:
   - メッセージ読み込み時のログ
   - メッセージ要素作成時の詳細ログ（送信者ID、受信者ID、型情報）

### 編集したファイル
- `/Users/mizy/Dropbox/line-fake/js/app.js` - 型変換とデバッグログの追加
- `/Users/mizy/Dropbox/line-fake/api/messages.php` - IDの型変換
- `/Users/mizy/Dropbox/line-fake/api/users.php` - IDの型変換
- `/Users/mizy/Dropbox/line-fake/api/login.php` - IDの型変換
- `/Users/mizy/Dropbox/line-fake/api/register.php` - IDの型変換
- `/Users/mizy/Dropbox/line-fake/api/send_message.php` - IDの型変換

### 注意事項
1. `api/config.php` にデータベース接続情報とJWT_SECRETを設定する必要があります
2. PWAアイコン（192x192、512x512）を生成する必要があります
3. 本番環境ではHTTPSが必須です
4. さくらレンタルサーバーでMySQLデータベースを作成し、schema.sqlを実行してください
