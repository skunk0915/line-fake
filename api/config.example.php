<?php
// データベース設定（さくらレンタルサーバー用）
// このファイルをコピーして config.php を作成し、実際の値を設定してください

define('DB_HOST', 'mysql123.db.sakura.ne.jp');  // さくらのMySQLホスト名
define('DB_NAME', 'your_database_name');         // データベース名
define('DB_USER', 'your_database_user');         // データベースユーザー名
define('DB_PASS', 'your_database_password');     // データベースパスワード

// セキュリティ設定
// 本番環境では必ずランダムで強力な文字列に変更してください
define('JWT_SECRET', 'your-secret-key-change-this-in-production-min-32-chars');

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

// 簡易JWT生成
function generateToken($userId) {
    $header = base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
    $payload = base64_encode(json_encode([
        'user_id' => $userId,
        'exp' => time() + (86400 * 30) // 30日間有効
    ]));
    $signature = hash_hmac('sha256', "$header.$payload", JWT_SECRET);
    return "$header.$payload.$signature";
}

// JWT検証
function verifyToken($token) {
    if (!$token) return false;

    $parts = explode('.', $token);
    if (count($parts) !== 3) return false;

    [$header, $payload, $signature] = $parts;
    $validSignature = hash_hmac('sha256', "$header.$payload", JWT_SECRET);

    if ($signature !== $validSignature) return false;

    $payloadData = json_decode(base64_decode($payload), true);

    if ($payloadData['exp'] < time()) return false;

    return $payloadData['user_id'];
}

// 認証チェック
function requireAuth() {
    $headers = getallheaders();
    $token = null;

    if (isset($headers['Authorization'])) {
        $token = str_replace('Bearer ', '', $headers['Authorization']);
    }

    $userId = verifyToken($token);
    if (!$userId) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => '認証が必要です']);
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
