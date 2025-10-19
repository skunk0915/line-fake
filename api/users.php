<?php
require_once 'config.php';

$userId = requireAuth();

try {
    $pdo = getDB();

    // 全ユーザーを取得（自分以外）
    $stmt = $pdo->prepare('
        SELECT
            u.id,
            u.name,
            u.email,
            (
                SELECT m.message
                FROM messages m
                WHERE (m.sender_id = u.id AND m.receiver_id = ?)
                   OR (m.sender_id = ? AND m.receiver_id = u.id)
                ORDER BY m.created_at DESC
                LIMIT 1
            ) as last_message
        FROM users u
        WHERE u.id != ?
        ORDER BY u.name ASC
    ');
    $stmt->execute([$userId, $userId, $userId]);
    $users = $stmt->fetchAll();

    // IDを整数型に変換
    foreach ($users as &$user) {
        $user['id'] = (int)$user['id'];
    }

    echo json_encode([
        'success' => true,
        'users' => $users
    ]);

} catch (PDOException $e) {
    error_log('Users fetch error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'ユーザー取得でエラーが発生しました']);
}
?>
