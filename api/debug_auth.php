<?php
require_once 'config.php';

header('Content-Type: text/plain; charset=utf-8');

echo "=== 認証デバッグ ===\n\n";

// ヘッダー情報
$headers = getallheaders();
echo "受信したヘッダー:\n";
foreach ($headers as $key => $value) {
    if (stripos($key, 'auth') !== false || stripos($key, 'content') !== false) {
        echo "$key: $value\n";
    }
}
echo "\n";

// Authorizationヘッダー
$authHeader = $headers['Authorization'] ?? 'なし';
echo "Authorization ヘッダー: $authHeader\n\n";

if ($authHeader !== 'なし') {
    $token = str_replace('Bearer ', '', $authHeader);
    echo "抽出されたトークン:\n$token\n\n";

    echo "トークン長: " . strlen($token) . "\n\n";

    // トークンの構造
    $parts = explode('.', $token);
    echo "トークンの部分数: " . count($parts) . "\n";

    if (count($parts) === 3) {
        echo "ヘッダー部分長: " . strlen($parts[0]) . "\n";
        echo "ペイロード部分長: " . strlen($parts[1]) . "\n";
        echo "署名部分長: " . strlen($parts[2]) . "\n\n";

        // デコード試行
        try {
            $payload = json_decode(base64UrlDecode($parts[1]), true);
            echo "デコードされたペイロード:\n";
            echo json_encode($payload, JSON_PRETTY_PRINT) . "\n\n";
        } catch (Exception $e) {
            echo "ペイロードのデコードエラー: " . $e->getMessage() . "\n\n";
        }

        // 検証試行
        $userId = verifyToken($token);
        echo "検証結果:\n";
        echo "ユーザーID: " . ($userId ?: 'false (検証失敗)') . "\n";

        if (!$userId) {
            // 署名を再計算して比較
            $expectedSignature = base64UrlEncode(hash_hmac('sha256', "$parts[0].$parts[1]", JWT_SECRET, true));
            echo "\n署名デバッグ:\n";
            echo "期待される署名: $expectedSignature\n";
            echo "受信した署名:   $parts[2]\n";
            echo "署名一致: " . ($expectedSignature === $parts[2] ? 'はい' : 'いいえ') . "\n";
        }
    }
}
?>
