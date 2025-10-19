-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- ホスト: mysql3102.db.sakura.ne.jp
-- 生成日時: 2025 年 10 月 19 日 22:20
-- サーバのバージョン： 8.0.39
-- PHP のバージョン: 8.2.16

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- データベース: `mizy_line`
--

-- --------------------------------------------------------

--
-- テーブルの構造 `messages`
--

CREATE TABLE `messages` (
  `id` int NOT NULL,
  `sender_id` int NOT NULL,
  `receiver_id` int NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL,
  `is_read` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- テーブルのデータのダンプ `messages`
--

INSERT INTO `messages` (`id`, `sender_id`, `receiver_id`, `message`, `created_at`, `is_read`) VALUES
(9, 7, 6, '私はカラスキです', '2025-10-19 21:18:19', 0),
(10, 6, 7, '私はスカンクです', '2025-10-19 21:18:39', 0),
(11, 7, 6, 'あなたは誰ですか', '2025-10-19 21:18:52', 0),
(12, 6, 7, 'スカンク言うてるやん', '2025-10-19 21:19:00', 0),
(13, 6, 7, '私がスカンク', '2025-10-19 21:28:20', 0),
(14, 7, 6, '私はからすき', '2025-10-19 21:33:21', 0),
(15, 6, 7, 'くわーてぃすかんく', '2025-10-19 22:19:07', 0),
(16, 7, 6, '*♪¥', '2025-10-19 22:19:39', 0);

-- --------------------------------------------------------

--
-- テーブルの構造 `push_subscriptions`
--

CREATE TABLE `push_subscriptions` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `endpoint` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `p256dh` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `auth` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- テーブルのデータのダンプ `push_subscriptions`
--

INSERT INTO `push_subscriptions` (`id`, `user_id`, `endpoint`, `p256dh`, `auth`, `user_agent`, `created_at`, `updated_at`) VALUES
(1, 7, 'https://fcm.googleapis.com/fcm/send/ex0KoG5ek_Y:APA91bEX5gOES--fK5F4SlMztHK_uHV5qJU8XG_NMG5fWI2Kyhaf0LOKCRb_B5nymqr1_o0hBY3XsJzvla4H5sTBDlPOlfIY0lUFpXOn_suFQUQR1O5AA5j0tHYdUSMvObveQoGU1RDh', 'BCKBTy7WED387lr-O4DIgpQqf_26YRgrGXzb5YMtUuJoh1guGYhlkX3LwFoGOE_-MLBsjjcEpvtJ2l0fSaG741Q', 'vGv6hDFJOOSZ54qaOKUsmw', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-19 13:18:29', '2025-10-19 13:18:29'),
(2, 7, 'https://fcm.googleapis.com/fcm/send/dmXYP44IfjM:APA91bHb9b2386v3N8s71H1FhSvckNo-KVRys_5mbE6sdb0QNF6vlHxeTrqU2-3zAJ7ePdYO3uITinRPWu2ebN5ru4U7q5bylUh_buP7tZZBvtZFpgF-zlTZ5Pucgzx-c8PzhJBeKoT8', 'BCp4n0-6QhGe4lULxRFaF2zKlV2FWdD7J32QiAAEhXtMt5mx_7PIQ6qM9MTCofpOv5G57l7V1FTo67O4ecQm2qc', '37G9F3eJ2zz2NmIkz2yTdQ', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-19 13:18:29', '2025-10-19 13:18:29'),
(3, 7, 'https://fcm.googleapis.com/fcm/send/ckk93_mXoSY:APA91bF_m2AxFg5k-ThAfowjHK0TYxhPdV0D8A8GX2cZNGdX1g7_ffORSMhZp9cpxPJuDTlyUgWwHiMF_9-4hI8QmnGetetLlVJbvSRmJiIXL-uYIl5rUzFdEh6XrtNF8rzLIIIM0EuH', 'BOy8GQ3dDDBRmL0GX8MQh1bG2xL9J04ZwpQcia23HmS4ZFOTmrWQE2C-x6xi80mSFhC9E63i0AuqUL3epSXripU', 'dUge-C5iqfphcY9IRzqMhg', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-19 13:18:29', '2025-10-19 13:18:29'),
(4, 7, 'https://fcm.googleapis.com/fcm/send/dbvIcBvNQHQ:APA91bENeOtR9-PxiGZzJ1ZmvaZjBkpFqU-ngaoeIbK95MPZHUZosFqgovUyiq_aetP3-Uo8nzQmsiZm3qubY5InCDr1vTYeK424pkDv4OnLiB7sNmoYlMeCXhPxZBpmUezATgRb1Jan', 'BCpV8Hw5EJnLgIwc3UwpEzlbxZLTwk_cWcGbYlpa9wv-lDe5oQrewog-DH1OaEQjeRRts7oB0TTN0Q6_0YRMLsw', 'jRPq0472BKD6pqEEsKC97g', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-19 13:18:29', '2025-10-19 13:18:29'),
(5, 7, 'https://fcm.googleapis.com/fcm/send/ehYKLNfLTC8:APA91bF6R5aIzR6V0n55wrDPHF9ObO6DaC8RAT-u8_WGPuqLgrjuibH0PGAoSqGDy4pX1cnj-kUY_VU7q3dTSGZgKvTG_1kYb9k115A74HWaILAdRyuPwk9QrZFgC8jBTwlMqwRJBVb4', 'BBbUZRRiafdHHz0D_9W348wVLVIHfbyMtECaaO8UVPo-OtdvE3vDOSpAHrwKak8gPi5WDdizPKJYNGCi333s4gI', 'yWBGne9fjoWvRrNLspgMeg', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-19 13:18:29', '2025-10-19 13:18:29'),
(6, 7, 'https://fcm.googleapis.com/fcm/send/c2r7gpfmRIw:APA91bFJyCwEDY8IJZUxdhjg7lAWJdfilLzhIdZyKjCkqVtRhdvY2gnap3Gd2AIZG5i-Mt3GtL6mO_1i0PjoA-JDSBTvXzwbASKex988RLZZczmnN_PgL6OQ9NekfEsaqN2htKUS20AQ', 'BGmDogfJyTrVQvtvr4ISImV1GTTlmB7vFoRvdsVMlp3URJ_o3y-vxCe0ZInosGUKn-KkBI91wa1H9qOH8YfqtMA', 'xpdzfg9lu_4sQBD7WIUvgw', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-19 13:18:29', '2025-10-19 13:18:29'),
(7, 6, 'https://web.push.apple.com/QI85gB4pMKO05TQqQg1oa0SvaKkEPx8EGVHvQlEXvLvLtEyca00m3S-PJywGFGuEfCFJAlGDXskp5eTsvyGnLSwEU87I7IyQKAg-LgWscVMiC0p4i7ctCuehiIMd0Q-5-uvKT7WmPsCQnuooI_FL-NKL62GeYd1Oa27UNz4pX7Y', 'BEH8X1DqKNBf3WaT7_Imet6hlZsQJf2mKEkLL-_SZX5kXyw2QpIabbS0pp063pkzMuncGqKTc_HMQySct_bqA30', 'wglaGlBv4Y2Tyu6JN-pRNw', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.6 Mobile/15E148 Safari/604.1', '2025-10-19 13:18:53', '2025-10-19 13:18:53');

-- --------------------------------------------------------

--
-- テーブルの構造 `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL,
  `last_login` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- テーブルのデータのダンプ `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `created_at`, `last_login`) VALUES
(6, 'mizy', 'skunk0915@gmail.com', '$2y$10$6NB8MWoWihAlWu8yaU9Ep.5O5KdW0CmTHTQkUr5ig2HPXuH0rfbwy', '2025-10-19 21:17:14', '2025-10-19 22:18:51'),
(7, 'colorscheme0915', 'colorscheme0915@gmail.com', '$2y$10$MxAqFWZzs8mDugyA0wGsm.Mc7nfECuESxf3sRaHKMGrmg63bbf6ea', '2025-10-19 21:17:56', '2025-10-19 22:18:15');

--
-- ダンプしたテーブルのインデックス
--

--
-- テーブルのインデックス `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `receiver_id` (`receiver_id`),
  ADD KEY `idx_sender_receiver` (`sender_id`,`receiver_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- テーブルのインデックス `push_subscriptions`
--
ALTER TABLE `push_subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- テーブルのインデックス `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- ダンプしたテーブルの AUTO_INCREMENT
--

--
-- テーブルの AUTO_INCREMENT `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- テーブルの AUTO_INCREMENT `push_subscriptions`
--
ALTER TABLE `push_subscriptions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- テーブルの AUTO_INCREMENT `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- ダンプしたテーブルの制約
--

--
-- テーブルの制約 `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- テーブルの制約 `push_subscriptions`
--
ALTER TABLE `push_subscriptions`
  ADD CONSTRAINT `push_subscriptions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
