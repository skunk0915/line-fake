<?php
require_once 'config.php';

// POSTデータ取得
$input = json_decode(file_get_contents('php://input'), true);

$name = trim($input['name'] ?? '');
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';

// バリデーション
if (empty($name) || empty($email) || empty($password)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'すべての項目を入力してください']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'メールアドレスの形式が正しくありません']);
    exit;
}

if (strlen($password) < 6) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'パスワードは6文字以上で設定してください']);
    exit;
}

try {
    $pdo = getDB();

    // メールアドレス重複チェック
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);

    if ($stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'このメールアドレスは既に登録されています']);
        exit;
    }

    // ユーザー登録
    $hashedPassword = hashPassword($password);
    $stmt = $pdo->prepare('INSERT INTO users (name, email, password, created_at) VALUES (?, ?, ?, NOW())');
    $stmt->execute([$name, $email, $hashedPassword]);

    $userId = (int)$pdo->lastInsertId();
    $token = generateToken($userId);

    echo json_encode([
        'success' => true,
        'message' => '登録が完了しました',
        'user' => [
            'id' => $userId,
            'name' => $name,
            'email' => $email,
            'token' => $token
        ]
    ]);

} catch (PDOException $e) {
    error_log('Registration error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => '登録処理でエラーが発生しました']);
}
?>
