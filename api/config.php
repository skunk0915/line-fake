<?php
// データベース設定（さくらレンタルサーバー用）
// 本番環境では config/database.php から読み込んでください

define('DB_HOST', 'mysql80.mizy.sakura.ne.jp');
define('DB_NAME', 'mizy_line');
define('DB_USER', 'mizy_line');
define('DB_PASS', '8rjcp4ck');

// セキュリティ設定
define('JWT_SECRET', 'your-secret-key-change-this-in-production');

// タイムゾーン設定
date_default_timezone_set('Asia/Tokyo');

// データベース接続
function getDB() {
    try {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        error_log('Database connection failed: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'データベース接続エラー']);
        exit;
    }
}

// CORS設定
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

// OPTIONSリクエストへの対応
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// getallheaders()の代替実装（一部環境で動作しない場合のため）
if (!function_exists('getallheaders')) {
    function getallheaders() {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }
}

// Base64 URL-safe エンコード
function base64UrlEncode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

// Base64 URL-safe デコード
function base64UrlDecode($data) {
    return base64_decode(strtr($data, '-_', '+/'));
}

// 簡易JWT生成
function generateToken($userId) {
    $header = base64UrlEncode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
    $payload = base64UrlEncode(json_encode([
        'user_id' => $userId,
        'exp' => time() + (86400 * 30) // 30日間有効
    ]));
    $signature = base64UrlEncode(hash_hmac('sha256', "$header.$payload", JWT_SECRET, true));
    return "$header.$payload.$signature";
}

// JWT検証
function verifyToken($token) {
    if (!$token) return false;

    $parts = explode('.', $token);
    if (count($parts) !== 3) return false;

    [$header, $payload, $signature] = $parts;
    $validSignature = base64UrlEncode(hash_hmac('sha256', "$header.$payload", JWT_SECRET, true));

    if ($signature !== $validSignature) return false;

    $payloadData = json_decode(base64UrlDecode($payload), true);

    if (!$payloadData || $payloadData['exp'] < time()) return false;

    return $payloadData['user_id'];
}

// 認証チェック
function requireAuth() {
    $token = null;

    // Authorizationヘッダーを複数の方法で取得を試みる
    $headers = getallheaders();

    // 方法1: getallheaders()から取得
    if (isset($headers['Authorization'])) {
        $token = $headers['Authorization'];
    }
    // 方法2: 小文字のキーで試す
    elseif (isset($headers['authorization'])) {
        $token = $headers['authorization'];
    }
    // 方法3: $_SERVERから直接取得
    elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $token = $_SERVER['HTTP_AUTHORIZATION'];
    }
    // 方法4: Apache環境での代替ヘッダー
    elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $token = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    }

    // "Bearer "プレフィックスを削除
    if ($token) {
        $token = str_replace('Bearer ', '', $token);
        $token = str_replace('bearer ', '', $token);
    }

    // デバッグログ（本番環境では削除してください）
    error_log('All headers: ' . json_encode($headers));
    error_log('Authorization token: ' . ($token ?? 'null'));

    $userId = verifyToken($token);
    error_log('User ID from token: ' . ($userId ?: 'false'));

    if (!$userId) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => '認証が必要です',
            'debug' => [
                'token_received' => $token ? 'yes' : 'no',
                'token_length' => $token ? strlen($token) : 0
            ]
        ]);
        exit;
    }

    return $userId;
}

// パスワードハッシュ化
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

// パスワード検証
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}
?>
