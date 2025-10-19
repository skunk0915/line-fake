<?php
require_once __DIR__ . '/../api/config.php';

try {
    $pdo = getDB();

    // プッシュ通知のサブスクリプションテーブルを作成
    $sql = "
    CREATE TABLE IF NOT EXISTS push_subscriptions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        endpoint TEXT NOT NULL,
        p256dh VARCHAR(255) NOT NULL,
        auth VARCHAR(255) NOT NULL,
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_user_id (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";

    $pdo->exec($sql);
    echo "✓ push_subscriptions テーブルを作成しました\n";

    // 既存のテーブル構造を確認
    $stmt = $pdo->query("DESCRIBE push_subscriptions");
    echo "\nテーブル構造:\n";
    echo "-----------------------------------\n";
    while ($row = $stmt->fetch()) {
        echo sprintf("%-20s %-20s\n", $row['Field'], $row['Type']);
    }
    echo "-----------------------------------\n";

    echo "\n✓ セットアップ完了!\n";

} catch (PDOException $e) {
    echo "✗ エラー: " . $e->getMessage() . "\n";
    exit(1);
}
