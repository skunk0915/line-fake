// グローバル変数
let currentUser = null;
let currentChatUser = null;
let messagePollingInterval = null;
let swRegistration = null;

// API エンドポイント
const API_BASE = 'api';

// VAPID公開鍵（サーバーから取得した値）
const VAPID_PUBLIC_KEY = 'BG4f8hDmmbHJux21u__aOes68TJRy7VG6cw-zItJ49Y_R6WqwHjfApHlIXeZa77e7LH-Ph05r81WVnhWdw9LAhk';

// ページ切り替え
function showPage(pageId) {
    document.querySelectorAll('.page').forEach(page => {
        page.classList.remove('active');
    });
    document.getElementById(pageId).classList.add('active');
}

// エラー表示
function showError(message, formId) {
    const form = document.getElementById(formId);
    let errorDiv = form.querySelector('.error-message');

    if (!errorDiv) {
        errorDiv = document.createElement('div');
        errorDiv.className = 'error-message';
        form.insertBefore(errorDiv, form.firstChild);
    }

    errorDiv.textContent = message;
    setTimeout(() => errorDiv.remove(), 5000);
}

// ローカルストレージ管理
function saveUser(user) {
    localStorage.setItem('currentUser', JSON.stringify(user));
    currentUser = user;
}

function loadUser() {
    const user = localStorage.getItem('currentUser');
    if (user) {
        currentUser = JSON.parse(user);
        return true;
    }
    return false;
}

function clearUser() {
    localStorage.removeItem('currentUser');
    currentUser = null;
}

// API呼び出し
async function apiCall(endpoint, method = 'GET', data = null) {
    const options = {
        method: method,
        headers: {
            'Content-Type': 'application/json',
        }
    };

    if (currentUser && currentUser.token) {
        options.headers['Authorization'] = 'Bearer ' + currentUser.token;
        console.log('Authorization ヘッダー:', options.headers['Authorization']);
        console.log('トークン長:', currentUser.token.length);
    } else {
        console.log('トークンなし:', { currentUser });
    }

    if (data) {
        options.body = JSON.stringify(data);
    }

    console.log(`API呼び出し: ${method} ${endpoint}`, options);

    try {
        const response = await fetch(`${API_BASE}/${endpoint}`, options);
        const result = await response.json();

        if (!response.ok) {
            console.error(`API エラー応答 (${response.status}):`, result);
            // 認証エラーの場合、ローカルストレージをクリアしてログインページへ
            if (response.status === 401) {
                clearUser();
                showPage('loginPage');
            }
            throw new Error(result.message || 'エラーが発生しました');
        }

        return result;
    } catch (error) {
        console.error('API Error:', error);
        throw error;
    }
}

// 新規登録
document.getElementById('registerForm').addEventListener('submit', async (e) => {
    e.preventDefault();

    const name = document.getElementById('registerName').value;
    const email = document.getElementById('registerEmail').value;
    const password = document.getElementById('registerPassword').value;

    try {
        const result = await apiCall('register.php', 'POST', { name, email, password });
        console.log('登録成功:', result);

        if (result.success) {
            saveUser(result.user);
            console.log('ユーザー情報保存完了:', currentUser);
            await loadChatList();
            showPage('chatListPage');
        }
    } catch (error) {
        console.error('登録エラー:', error);
        showError(error.message, 'registerForm');
    }
});

// ログイン
document.getElementById('loginForm').addEventListener('submit', async (e) => {
    e.preventDefault();

    const email = document.getElementById('loginEmail').value;
    const password = document.getElementById('loginPassword').value;

    try {
        const result = await apiCall('login.php', 'POST', { email, password });
        console.log('ログイン成功:', result);

        if (result.success) {
            saveUser(result.user);
            console.log('ユーザー情報保存完了:', currentUser);
            await loadChatList();
            showPage('chatListPage');

            // ログイン後にプッシュ通知の許可を求める
            setTimeout(() => {
                requestNotificationPermission();
            }, 500);
        }
    } catch (error) {
        console.error('ログインエラー:', error);
        showError(error.message, 'loginForm');
    }
});

// ログアウト
document.getElementById('logoutBtn').addEventListener('click', () => {
    clearUser();
    if (messagePollingInterval) {
        clearInterval(messagePollingInterval);
    }
    showPage('loginPage');
});

// 画面切り替え
document.getElementById('showRegister').addEventListener('click', (e) => {
    e.preventDefault();
    showPage('registerPage');
});

document.getElementById('showLogin').addEventListener('click', (e) => {
    e.preventDefault();
    showPage('loginPage');
});

document.getElementById('backBtn').addEventListener('click', () => {
    if (messagePollingInterval) {
        clearInterval(messagePollingInterval);
    }
    showPage('chatListPage');
});

// ユーザーリスト読み込み
async function loadChatList() {
    try {
        const result = await apiCall('users.php');

        if (result.success) {
            const userList = document.getElementById('userList');
            userList.innerHTML = '';

            result.users.forEach(user => {
                if (user.id !== currentUser.id) {
                    const userItem = createUserItem(user);
                    userList.appendChild(userItem);
                }
            });
        }
    } catch (error) {
        console.error('ユーザーリスト読み込みエラー:', error);
        // 認証エラーの場合は既にログインページに遷移しているのでここでは何もしない
    }
}

// ユーザーアイテム作成
function createUserItem(user) {
    const div = document.createElement('div');
    div.className = 'user-item';
    div.onclick = () => openChat(user);

    const initial = user.name.charAt(0).toUpperCase();

    div.innerHTML = `
        <div class="user-avatar">${initial}</div>
        <div class="user-info">
            <div class="user-name">${user.name}</div>
            <div class="last-message">${user.last_message || 'メッセージを送信'}</div>
        </div>
    `;

    return div;
}

// チャットを開く
function openChat(user) {
    currentChatUser = user;
    document.getElementById('chatUserName').textContent = user.name;
    showPage('chatPage');
    loadMessages();

    // 3秒ごとに新しいメッセージをチェック
    if (messagePollingInterval) {
        clearInterval(messagePollingInterval);
    }
    messagePollingInterval = setInterval(loadMessages, 3000);
}

// メッセージ読み込み
async function loadMessages() {
    try {
        const result = await apiCall(`messages.php?user_id=${currentChatUser.id}`);

        if (result.success) {
            console.log('メッセージ読み込み成功:', {
                currentUserId: currentUser.id,
                chatUserId: currentChatUser.id,
                messageCount: result.messages.length,
                messages: result.messages
            });
            displayMessages(result.messages);
        }
    } catch (error) {
        console.error('メッセージ読み込みエラー:', error);
    }
}

// メッセージ表示
function displayMessages(messages) {
    const container = document.getElementById('messagesContainer');
    const shouldScroll = container.scrollHeight - container.scrollTop === container.clientHeight;

    container.innerHTML = '';

    messages.forEach(message => {
        const messageDiv = createMessageElement(message);
        container.appendChild(messageDiv);
    });

    if (shouldScroll || messages.length > 0) {
        container.scrollTop = container.scrollHeight;
    }
}

// メッセージ要素作成
function createMessageElement(message) {
    const div = document.createElement('div');
    // 型を統一して比較（PHPから返される値は文字列の可能性があるため）
    const isSent = Number(message.sender_id) === Number(currentUser.id);
    div.className = `message ${isSent ? 'sent' : 'received'}`;

    console.log('メッセージ要素作成:', {
        messageId: message.id,
        senderId: message.sender_id,
        senderIdType: typeof message.sender_id,
        receiverId: message.receiver_id,
        currentUserId: currentUser.id,
        currentUserIdType: typeof currentUser.id,
        isSent: isSent,
        className: div.className
    });

    const time = new Date(message.created_at).toLocaleTimeString('ja-JP', {
        hour: '2-digit',
        minute: '2-digit'
    });

    div.innerHTML = `
        <div class="message-bubble">
            ${message.message}
            <div class="message-time">${time}</div>
        </div>
    `;

    return div;
}

// メッセージ送信
document.getElementById('sendBtn').addEventListener('click', sendMessage);
document.getElementById('messageInput').addEventListener('keypress', (e) => {
    if (e.key === 'Enter') {
        sendMessage();
    }
});

async function sendMessage() {
    const input = document.getElementById('messageInput');
    const message = input.value.trim();

    if (!message) return;

    try {
        const result = await apiCall('send_message.php', 'POST', {
            receiver_id: currentChatUser.id,
            message: message
        });

        if (result.success) {
            input.value = '';
            loadMessages();
        }
    } catch (error) {
        console.error('メッセージ送信エラー:', error);
        alert('メッセージの送信に失敗しました');
    }
}

// Base64 URL-safe文字列をUint8Arrayに変換
function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding)
        .replace(/\-/g, '+')
        .replace(/_/g, '/');

    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);

    for (let i = 0; i < rawData.length; ++i) {
        outputArray[i] = rawData.charCodeAt(i);
    }
    return outputArray;
}

// プッシュ通知の購読
async function subscribeToPushNotifications() {
    if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
        console.log('このブラウザはプッシュ通知をサポートしていません');
        return false;
    }

    try {
        // Service Workerの準備を待つ
        const registration = await navigator.serviceWorker.ready;
        swRegistration = registration;

        // 既存の購読を確認
        let subscription = await registration.pushManager.getSubscription();

        if (!subscription) {
            // 新規購読
            console.log('プッシュ通知の購読を開始します');

            const applicationServerKey = urlBase64ToUint8Array(VAPID_PUBLIC_KEY);
            subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: applicationServerKey
            });

            console.log('プッシュ通知の購読に成功しました');
        } else {
            console.log('既にプッシュ通知を購読しています');
        }

        // サブスクリプション情報をサーバーに送信
        await sendSubscriptionToServer(subscription);
        return true;

    } catch (error) {
        console.error('プッシュ通知の購読に失敗しました:', error);
        return false;
    }
}

// サブスクリプション情報をサーバーに送信
async function sendSubscriptionToServer(subscription) {
    try {
        const result = await apiCall('save_subscription.php', 'POST', {
            subscription: JSON.stringify(subscription)
        });

        if (result.success) {
            console.log('サブスクリプション情報をサーバーに保存しました');
        } else {
            console.error('サブスクリプション情報の保存に失敗しました');
        }
    } catch (error) {
        console.error('サブスクリプション送信エラー:', error);
    }
}

// プッシュ通知の許可をリクエスト
async function requestNotificationPermission() {
    if (!('Notification' in window)) {
        console.log('このブラウザは通知をサポートしていません');
        return false;
    }

    if (Notification.permission === 'granted') {
        // 既に許可されている
        await subscribeToPushNotifications();
        return true;
    }

    if (Notification.permission !== 'denied') {
        // 許可をリクエスト
        const permission = await Notification.requestPermission();

        if (permission === 'granted') {
            console.log('通知が許可されました');
            await subscribeToPushNotifications();
            return true;
        } else {
            console.log('通知が拒否されました');
            return false;
        }
    }

    return false;
}

// 初期化
async function init() {
    if (loadUser()) {
        // ユーザー情報があればチャットリストを読み込み
        try {
            await loadChatList();
            showPage('chatListPage');

            // ログイン後にプッシュ通知の許可を求める
            // ユーザーアクションの後に実行するため、少し遅延させる
            setTimeout(() => {
                requestNotificationPermission();
            }, 1000);
        } catch (error) {
            // トークンが無効な場合は既にログインページに遷移している
            console.log('初期化時のトークン検証失敗、ログイン画面に遷移します');
        }
    } else {
        showPage('loginPage');
    }
}

init();
