<?php
/**
 * VAPID キー生成スクリプト
 * Web Push通知に必要な公開鍵と秘密鍵を生成します
 *
 * 使用方法:
 * php api/generate_vapid_keys.php
 *
 * 出力された鍵をconfig.phpに設定してください
 */

// Base64URLエンコード用の関数
function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

// OpenSSLで楕円曲線暗号(prime256v1 / P-256)のキーペアを生成
$config = [
    'curve_name' => 'prime256v1',
    'private_key_type' => OPENSSL_KEYTYPE_EC,
];

$keyResource = openssl_pkey_new($config);
if ($keyResource === false) {
    die("Error: Failed to generate key pair\n");
}

// 鍵の詳細情報を取得
$details = openssl_pkey_get_details($keyResource);
if ($details === false) {
    die("Error: Failed to get key details\n");
}

// 秘密鍵をエクスポート
openssl_pkey_export($keyResource, $privateKeyPEM);

// 公開鍵は既に$detailsに含まれている
$publicKeyPEM = $details['key'];

// 秘密鍵と公開鍵をファイルに一時保存してから読み込む方法を使用
$tempPrivateKeyFile = tempnam(sys_get_temp_dir(), 'vapid_private_');
$tempPublicKeyFile = tempnam(sys_get_temp_dir(), 'vapid_public_');

// 秘密鍵を保存
file_put_contents($tempPrivateKeyFile, $privateKeyPEM);

// 公開鍵を保存
file_put_contents($tempPublicKeyFile, $publicKeyPEM);

// 秘密鍵をDER形式に変換
exec("openssl ec -in $tempPrivateKeyFile -outform DER 2>/dev/null", $output, $returnCode);
$privateKeyDER = implode('', array_map('base64_decode', $output));

// 別の方法: 直接DER形式で出力
$privateKeyDER = shell_exec("openssl ec -in $tempPrivateKeyFile -outform DER 2>/dev/null");

// 公開鍵をDER形式に変換
$publicKeyDER = shell_exec("openssl ec -in $tempPrivateKeyFile -pubout -outform DER 2>/dev/null");

// 一時ファイルを削除
unlink($tempPrivateKeyFile);
unlink($tempPublicKeyFile);

// DER形式からキーバイトを抽出
// 秘密鍵: SEQ -> INT(version) -> OCTET STRING(32 bytes private key) -> ...
// 簡易的に最初に見つかる32バイトのOCTET STRINGを探す
$privateKeyBytes = '';
$publicKeyBytes = '';

// 秘密鍵のバイト列を探す(DER解析)
if ($privateKeyDER && strlen($privateKeyDER) > 32) {
    // OCTET STRING タグ (0x04) を探して、その後の32バイトを取得
    $pos = strpos($privateKeyDER, "\x04\x20"); // 0x04 = OCTET STRING, 0x20 = 32 bytes
    if ($pos !== false) {
        $privateKeyBytes = substr($privateKeyDER, $pos + 2, 32);
    } else {
        // 別のパターンを試す
        $pos = strpos($privateKeyDER, "\x04\x21"); // 0x21 = 33 bytes (先頭に0x00がある場合)
        if ($pos !== false) {
            $privateKeyBytes = substr($privateKeyDER, $pos + 3, 32); // 0x00をスキップ
        }
    }
}

// 公開鍵のバイト列を取得(最後の65バイト)
if ($publicKeyDER && strlen($publicKeyDER) >= 65) {
    $publicKeyBytes = substr($publicKeyDER, -65);
}

// バイト列が取得できなかった場合のフォールバック
if (empty($privateKeyBytes) || empty($publicKeyBytes)) {
    // 詳細情報から取得を試みる
    if (isset($details['ec']['d'])) {
        // 秘密鍵の値(d)
        $privateKeyBytes = $details['ec']['d'];
        while (strlen($privateKeyBytes) < 32) {
            $privateKeyBytes = "\x00" . $privateKeyBytes;
        }
        if (strlen($privateKeyBytes) > 32) {
            $privateKeyBytes = substr($privateKeyBytes, -32);
        }
    }

    if (isset($details['ec']['x']) && isset($details['ec']['y'])) {
        // 公開鍵の座標(x, y)
        $x = $details['ec']['x'];
        $y = $details['ec']['y'];

        // パディング
        while (strlen($x) < 32) {
            $x = "\x00" . $x;
        }
        while (strlen($y) < 32) {
            $y = "\x00" . $y;
        }

        // 非圧縮形式: 0x04 + x(32bytes) + y(32bytes)
        $publicKeyBytes = "\x04" . substr($x, -32) . substr($y, -32);
    }
}

// Base64URLエンコード
$privateKeyBase64 = base64url_encode($privateKeyBytes);
$publicKeyBase64 = base64url_encode($publicKeyBytes);

// 結果を表示
echo "=================================================\n";
echo "VAPID Keys Generated Successfully!\n";
echo "=================================================\n\n";

echo "Public Key (公開鍵) - フロントエンドで使用:\n";
echo $publicKeyBase64 . "\n\n";

echo "Private Key (秘密鍵) - サーバーサイドで使用:\n";
echo $privateKeyBase64 . "\n\n";

echo "=================================================\n";
echo "次のステップ:\n";
echo "=================================================\n";
echo "1. api/config.php を開く\n";
echo "2. 以下の設定を追加してください:\n\n";

echo "// VAPID Keys for Web Push\n";
echo "define('VAPID_PUBLIC_KEY', '" . $publicKeyBase64 . "');\n";
echo "define('VAPID_PRIVATE_KEY', '" . $privateKeyBase64 . "');\n";
echo "define('VAPID_SUBJECT', 'mailto:your-email@example.com'); // 実際のメールアドレスに変更\n\n";

echo "=================================================\n";
echo "セキュリティ注意事項:\n";
echo "=================================================\n";
echo "- Private Key は絶対に公開しないでください\n";
echo "- config.php が Git にコミットされないことを確認してください\n";
echo "- Public Key はフロントエンド(JavaScript)で使用します\n";
echo "=================================================\n";
