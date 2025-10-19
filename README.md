# LINE風チャットアプリケーション

メールアドレスとパスワードで会員登録し、LINE風のUIでメッセージの送受信ができるPWA対応のチャットアプリケーションです。さくらレンタルサーバーで動作します。

## 機能

- ✅ メールアドレス・パスワードによる会員登録
- ✅ ログイン・ログアウト機能
- ✅ LINE風のチャットUI
- ✅ リアルタイムメッセージ送受信（3秒ごとのポーリング）
- ✅ PWA対応（オフライン動作、ホーム画面追加）
- ✅ レスポンシブデザイン

## 技術スタック

- **フロントエンド**: HTML5, CSS3, JavaScript (Vanilla)
- **バックエンド**: PHP 7.4+
- **データベース**: MySQL 5.7+
- **PWA**: Service Worker, Web App Manifest

## ファイル構成

```
line-fake/
├── index.html              # メインHTMLファイル
├── manifest.json          # PWAマニフェスト
├── service-worker.js      # Service Worker
├── css/
│   └── style.css          # スタイルシート
├── js/
│   └── app.js             # フロントエンドロジック
├── api/
│   ├── config.php         # データベース設定
│   ├── register.php       # 会員登録API
│   ├── login.php          # ログインAPI
│   ├── users.php          # ユーザー一覧取得API
│   ├── messages.php       # メッセージ取得API
│   └── send_message.php   # メッセージ送信API
├── database/
│   └── schema.sql         # データベーススキーマ
├── images/
│   ├── icon-192.png       # PWAアイコン（192x192）
│   └── icon-512.png       # PWAアイコン（512x512）
└── README.md
```

## さくらレンタルサーバーへのデプロイ手順

### 1. データベースの準備

1. さくらのコントロールパネルにログイン
2. 「データベースの設定」からMySQLデータベースを作成
3. phpMyAdminにアクセス
4. `database/schema.sql` の内容を実行してテーブルを作成

### 2. データベース設定の更新

`api/config.php` を編集し、データベース接続情報を更新:

```php
define('DB_HOST', 'mysql123.db.sakura.ne.jp');  // さくらのMySQLホスト
define('DB_NAME', 'your_database_name');         // データベース名
define('DB_USER', 'your_database_user');         // データベースユーザー名
define('DB_PASS', 'your_database_password');     // データベースパスワード
```

**重要**: `JWT_SECRET` も本番環境用のランダムな文字列に変更してください:

```php
define('JWT_SECRET', 'ランダムで長い文字列に変更してください');
```

### 3. ファイルのアップロード

FTPクライアント（FileZilla等）を使用して、すべてのファイルをさくらのウェブサーバーにアップロード:

- ホスト: `your-domain.sakura.ne.jp`
- アップロード先: `/home/your-account/www/` または任意のディレクトリ

### 4. PWAアイコンの準備

`images/` フォルダに以下のアイコン画像を配置:

- `icon-192.png` (192x192px)
- `icon-512.png` (512x512px)

オンラインツール（例: [Favicon Generator](https://realfavicongenerator.net/)）でアイコンを生成できます。

### 5. パーミッション設定

必要に応じてディレクトリのパーミッションを設定:

```bash
chmod 755 api/
chmod 644 api/*.php
```

### 6. .htaccess の設定（オプション）

ルートディレクトリに `.htaccess` を作成し、セキュリティとURLリライトを設定:

```apache
# PHP設定
php_flag display_errors Off
php_value upload_max_filesize 10M
php_value post_max_size 10M

# セキュリティヘッダー
<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-XSS-Protection "1; mode=block"
</IfModule>

# エラーページ
ErrorDocument 404 /index.html
```

### 7. 動作確認

1. ブラウザでサイトにアクセス: `https://your-domain.sakura.ne.jp/`
2. 新規登録画面からアカウントを作成
3. ログイン後、他のユーザーとメッセージの送受信をテスト
4. PWA機能の確認: モバイルで「ホーム画面に追加」

## ローカル開発環境での起動

### 必要なもの

- PHP 7.4以上
- MySQL 5.7以上

### セットアップ

1. リポジトリをクローン:
```bash
git clone https://github.com/your-repo/line-fake.git
cd line-fake
```

2. データベースを作成してスキーマをインポート:
```bash
mysql -u root -p
CREATE DATABASE line_chat;
exit
mysql -u root -p line_chat < database/schema.sql
```

3. `api/config.php` でローカルのデータベース設定を行う

4. PHPビルトインサーバーを起動:
```bash
php -S localhost:8000
```

5. ブラウザで `http://localhost:8000` にアクセス

## セキュリティに関する注意事項

本番環境では以下の対策を実施してください:

1. **HTTPS必須**: PWAはHTTPS環境が必須です
2. **JWT_SECRET**: ランダムで強力な秘密鍵を設定
3. **データベース認証情報**: 環境変数または別ファイルで管理
4. **入力検証**: すべてのユーザー入力を検証・サニタイズ
5. **パスワードポリシー**: より強力なパスワード要件の設定を検討
6. **レート制限**: API呼び出しのレート制限を実装

## トラブルシューティング

### データベース接続エラー

- `api/config.php` の接続情報を確認
- さくらのコントロールパネルでMySQLが有効か確認

### PWAが動作しない

- HTTPSでアクセスしているか確認
- Service Workerがブラウザでサポートされているか確認
- ブラウザの開発者ツールでService Workerの登録状況を確認

### メッセージが表示されない

- ブラウザの開発者ツールでネットワークエラーを確認
- APIレスポンスのエラーメッセージを確認
- データベースのテーブルが正しく作成されているか確認

## ライセンス

MIT License

## サポート

問題が発生した場合は、GitHubのIssuesで報告してください。
