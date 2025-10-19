<?php
require_once 'config.php';

$userId = requireAuth();

// POSTデータ取得
$input = json_decode(file_get_contents('php://input'), true);

$receiverId = $input['receiver_id'] ?? null;
$message = trim($input['message'] ?? '');

// バリデーション
if (!$receiverId || empty($message)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => '受信者とメッセージを指定してください']);
    exit;
}

try {
    $pdo = getDB();

    // 受信者が存在するか確認
    $stmt = $pdo->prepare('SELECT id FROM users WHERE id = ?');
    $stmt->execute([$receiverId]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => '受信者が見つかりません']);
        exit;
    }

    // メッセージを保存
    $stmt = $pdo->prepare('
        INSERT INTO messages (sender_id, receiver_id, message, created_at)
        VALUES (?, ?, ?, NOW())
    ');
    $stmt->execute([$userId, $receiverId, $message]);

    $messageId = (int)$pdo->lastInsertId();

    echo json_encode([
        'success' => true,
        'message_id' => $messageId,
        'message' => 'メッセージを送信しました'
    ]);

} catch (PDOException $e) {
    error_log('Send message error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'メッセージ送信でエラーが発生しました']);
}
?>
