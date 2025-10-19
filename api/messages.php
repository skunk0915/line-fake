<?php
require_once 'config.php';

$userId = requireAuth();
$otherUserId = $_GET['user_id'] ?? null;

if (!$otherUserId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ユーザーIDが指定されていません']);
    exit;
}

try {
    $pdo = getDB();

    // 2人のユーザー間のメッセージを取得
    $stmt = $pdo->prepare('
        SELECT
            id,
            sender_id,
            receiver_id,
            message,
            created_at
        FROM messages
        WHERE (sender_id = ? AND receiver_id = ?)
           OR (sender_id = ? AND receiver_id = ?)
        ORDER BY created_at ASC
    ');
    $stmt->execute([$userId, $otherUserId, $otherUserId, $userId]);
    $messages = $stmt->fetchAll();

    // IDを整数型に変換
    foreach ($messages as &$msg) {
        $msg['id'] = (int)$msg['id'];
        $msg['sender_id'] = (int)$msg['sender_id'];
        $msg['receiver_id'] = (int)$msg['receiver_id'];
    }

    echo json_encode([
        'success' => true,
        'messages' => $messages
    ]);

} catch (PDOException $e) {
    error_log('Messages fetch error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'メッセージ取得でエラーが発生しました']);
}
?>
