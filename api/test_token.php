<?php
require_once 'config.php';

// テスト用のユーザーID
$testUserId = 4;

// トークン生成
$token = generateToken($testUserId);

echo "生成されたトークン:\n";
echo $token . "\n\n";

// トークン検証
$verifiedUserId = verifyToken($token);

echo "検証結果:\n";
echo "ユーザーID: " . ($verifiedUserId ?: 'false') . "\n\n";

// トークンの各部分を表示
$parts = explode('.', $token);
echo "ヘッダー: " . $parts[0] . "\n";
echo "ペイロード: " . $parts[1] . "\n";
echo "署名: " . $parts[2] . "\n\n";

// ペイロードのデコード
echo "デコードされたペイロード:\n";
echo json_encode(json_decode(base64UrlDecode($parts[1]), true), JSON_PRETTY_PRINT) . "\n\n";

// Authorizationヘッダーのシミュレーション
echo "Authorization ヘッダー:\n";
echo "Bearer " . $token . "\n";
?>
