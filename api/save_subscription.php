<?php
require_once 'config.php';

// 認証チェック
$userId = requireAuth();

// リクエストボディを取得
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['subscription'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'サブスクリプション情報が必要です'
    ]);
    exit;
}

try {
    $subscription = json_decode($input['subscription'], true);

    if (!isset($subscription['endpoint']) || !isset($subscription['keys'])) {
        throw new Exception('無効なサブスクリプション形式です');
    }

    $endpoint = $subscription['endpoint'];
    $p256dh = $subscription['keys']['p256dh'] ?? '';
    $auth = $subscription['keys']['auth'] ?? '';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

    $pdo = getDB();

    // 同じエンドポイントが既に存在するかチェック
    $stmt = $pdo->prepare('
        SELECT id FROM push_subscriptions
        WHERE user_id = ? AND endpoint = ?
    ');
    $stmt->execute([$userId, $endpoint]);
    $existing = $stmt->fetch();

    if ($existing) {
        // 既存のサブスクリプションを更新
        $stmt = $pdo->prepare('
            UPDATE push_subscriptions
            SET p256dh = ?, auth = ?, user_agent = ?, updated_at = NOW()
            WHERE id = ?
        ');
        $stmt->execute([$p256dh, $auth, $userAgent, $existing['id']]);

        echo json_encode([
            'success' => true,
            'message' => 'サブスクリプション情報を更新しました',
            'subscription_id' => $existing['id']
        ]);
    } else {
        // 新規サブスクリプションを追加
        $stmt = $pdo->prepare('
            INSERT INTO push_subscriptions (user_id, endpoint, p256dh, auth, user_agent)
            VALUES (?, ?, ?, ?, ?)
        ');
        $stmt->execute([$userId, $endpoint, $p256dh, $auth, $userAgent]);

        echo json_encode([
            'success' => true,
            'message' => 'サブスクリプション情報を保存しました',
            'subscription_id' => $pdo->lastInsertId()
        ]);
    }

} catch (Exception $e) {
    error_log('Save subscription error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'サブスクリプションの保存に失敗しました: ' . $e->getMessage()
    ]);
}
