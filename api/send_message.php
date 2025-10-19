<?php
require_once 'config.php';
require_once 'WebPush.php';

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

    error_log("Message saved: ID=$messageId, From=$userId, To=$receiverId");

    // プッシュ通知を送信
    try {
        sendPushNotification($pdo, $receiverId, $userId, $message);
        error_log("Push notification sent successfully");
    } catch (Exception $e) {
        error_log("Push notification failed: " . $e->getMessage());
        // プッシュ通知が失敗してもメッセージ送信自体は成功とする
    }

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

/**
 * プッシュ通知を送信
 */
function sendPushNotification($pdo, $receiverId, $senderId, $messageText) {
    try {
        // 送信者の名前を取得
        $stmt = $pdo->prepare('SELECT name FROM users WHERE id = ?');
        $stmt->execute([$senderId]);
        $sender = $stmt->fetch();

        if (!$sender) {
            error_log("Sender not found: $senderId");
            return;
        }

        $senderName = $sender['name'];

        // 受信者のプッシュ通知サブスクリプションを取得
        $stmt = $pdo->prepare('
            SELECT endpoint, p256dh, auth
            FROM push_subscriptions
            WHERE user_id = ?
            ORDER BY updated_at DESC
        ');
        $stmt->execute([$receiverId]);
        $subscriptions = $stmt->fetchAll();

        if (empty($subscriptions)) {
            error_log("No push subscriptions found for user: $receiverId");
            return;
        }

        // Web Push インスタンスを作成
        $webPush = new WebPush(
            VAPID_PUBLIC_KEY,
            VAPID_PRIVATE_KEY,
            VAPID_SUBJECT
        );

        // 通知ペイロード
        $payload = json_encode([
            'title' => $senderName,
            'body' => mb_strlen($messageText) > 50 ? mb_substr($messageText, 0, 50) . '...' : $messageText,
            'icon' => '/images/favicon/icon-192.png',
            'badge' => '/images/favicon/icon-192.png',
            'tag' => 'chat-message-' . $senderId,
            'data' => [
                'url' => '/',
                'senderId' => $senderId,
                'senderName' => $senderName
            ]
        ]);

        // 各サブスクリプションに通知を送信
        $successCount = 0;
        $errorCount = 0;

        foreach ($subscriptions as $sub) {
            try {
                $subscription = json_encode([
                    'endpoint' => $sub['endpoint'],
                    'keys' => [
                        'p256dh' => $sub['p256dh'],
                        'auth' => $sub['auth']
                    ]
                ]);

                $result = $webPush->sendNotification($subscription, $payload);
                $successCount++;
                error_log("Push notification sent successfully to user $receiverId");

            } catch (Exception $e) {
                $errorCount++;
                error_log("Failed to send push notification: " . $e->getMessage());

                // エンドポイントが無効な場合は削除
                if (strpos($e->getMessage(), '404') !== false || strpos($e->getMessage(), '410') !== false) {
                    $stmt = $pdo->prepare('DELETE FROM push_subscriptions WHERE endpoint = ?');
                    $stmt->execute([$sub['endpoint']]);
                    error_log("Removed invalid subscription endpoint");
                }
            }
        }

        error_log("Push notification results: $successCount success, $errorCount errors");

    } catch (Exception $e) {
        error_log('Error in sendPushNotification: ' . $e->getMessage());
    }
}
?>
