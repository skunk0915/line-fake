<?php
require_once 'config.php';

// POSTデータ取得
$input = json_decode(file_get_contents('php://input'), true);

$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';

// バリデーション
if (empty($email) || empty($password)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'メールアドレスとパスワードを入力してください']);
    exit;
}

try {
    $pdo = getDB();

    // ユーザー検索
    $stmt = $pdo->prepare('SELECT id, name, email, password FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !verifyPassword($password, $user['password'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'メールアドレスまたはパスワードが正しくありません']);
        exit;
    }

    // 最終ログイン時刻更新
    $stmt = $pdo->prepare('UPDATE users SET last_login = NOW() WHERE id = ?');
    $stmt->execute([$user['id']]);

    $token = generateToken($user['id']);

    echo json_encode([
        'success' => true,
        'message' => 'ログインしました',
        'user' => [
            'id' => (int)$user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'token' => $token
        ]
    ]);

} catch (PDOException $e) {
    error_log('Login error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'ログイン処理でエラーが発生しました']);
}
?>
