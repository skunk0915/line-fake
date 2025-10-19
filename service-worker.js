const CACHE_NAME = 'line-chat-v3';
const urlsToCache = [
  './',
  './index.html',
  './css/style.css',
  './js/app.js',
  './images/favicon/icon-192.png',
  './images/favicon/icon-512.png',
  './images/favicon/apple-touch-icon.png'
];

// インストール時のキャッシュ
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        console.log('Opened cache');
        return cache.addAll(urlsToCache);
      })
  );
});

// キャッシュからの取得
self.addEventListener('fetch', event => {
  event.respondWith(
    caches.match(event.request)
      .then(response => {
        // キャッシュがあればそれを返す、なければネットワークから取得
        if (response) {
          return response;
        }
        return fetch(event.request);
      }
    )
  );
});

// 古いキャッシュの削除
self.addEventListener('activate', event => {
  const cacheWhitelist = [CACHE_NAME];
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          if (cacheWhitelist.indexOf(cacheName) === -1) {
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
});

// プッシュ通知受信時の処理
self.addEventListener('push', event => {
  console.log('[Service Worker] Push notification received:', event);

  // デフォルトの通知データ
  let notificationData = {
    title: '新しいメッセージ',
    body: 'メッセージが届きました',
    icon: './images/favicon/icon-192.png',
    badge: './images/favicon/icon-192.png',
    tag: 'chat-notification',
    requireInteraction: false,
    data: {
      url: './'
    }
  };

  // プッシュデータがあれば解析（オプション）
  if (event.data) {
    try {
      const data = event.data.json();
      console.log('[Service Worker] Push data:', data);
      if (data.title) notificationData.title = data.title;
      if (data.body) notificationData.body = data.body;
      if (data.url) notificationData.data.url = data.url;
      if (data.icon) notificationData.icon = data.icon;
      if (data.tag) notificationData.tag = data.tag;
    } catch (e) {
      // JSONパースに失敗した場合（空のペイロードの場合など）
      console.log('[Service Worker] No push data or parse error, using default message');
    }
  } else {
    console.log('[Service Worker] No push data, using default message');
  }

  console.log('[Service Worker] Showing notification:', notificationData);

  // 通知を表示
  event.waitUntil(
    self.registration.showNotification(notificationData.title, {
      body: notificationData.body,
      icon: notificationData.icon,
      badge: notificationData.badge,
      tag: notificationData.tag,
      requireInteraction: notificationData.requireInteraction,
      data: notificationData.data,
      vibrate: [200, 100, 200], // バイブレーションパターン
      actions: [
        {
          action: 'open',
          title: '開く'
        },
        {
          action: 'close',
          title: '閉じる'
        }
      ]
    })
  );
});

// 通知クリック時の処理
self.addEventListener('notificationclick', event => {
  console.log('Notification clicked:', event);

  event.notification.close();

  // 通知がクリックされたときの動作
  if (event.action === 'close') {
    // 閉じるアクションの場合は何もしない
    return;
  }

  // アプリを開く
  const urlToOpen = event.notification.data?.url || './';

  event.waitUntil(
    clients.matchAll({
      type: 'window',
      includeUncontrolled: true
    }).then(windowClients => {
      // 既に開いているウィンドウがあればそれをフォーカス
      for (let i = 0; i < windowClients.length; i++) {
        const client = windowClients[i];
        if (client.url === urlToOpen && 'focus' in client) {
          return client.focus();
        }
      }
      // なければ新しいウィンドウで開く
      if (clients.openWindow) {
        return clients.openWindow(urlToOpen);
      }
    })
  );
});
