/**
 * Push通知 購読フローと記録忘れリマインダーのトグル制御
 *
 * Alpine.js コンポーネント streakReminderToggle として t_account.php から呼ばれる。
 * 購読登録・解除と b_user.streak_reminder_enabled の更新を1ステップで扱う。
 */

function urlBase64ToUint8Array(base64String) {
  const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
  const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
  const raw = atob(base64);
  const output = new Uint8Array(raw.length);
  for (let i = 0; i < raw.length; ++i) {
    output[i] = raw.charCodeAt(i);
  }
  return output;
}

async function getOrRegisterSW() {
  if (!('serviceWorker' in navigator)) return null;
  const reg = await navigator.serviceWorker.getRegistration('/');
  if (reg) return reg;
  return await navigator.serviceWorker.register('/sw.js');
}

async function postJson(url, payload) {
  const resp = await fetch(url, {
    method: 'POST',
    credentials: 'same-origin',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload),
  });
  if (!resp.ok) throw new Error('HTTP ' + resp.status);
  return await resp.json();
}

function buildStreakReminderToggle(initialEnabled, vapidPublicKey) {
  return {
    enabled: !!initialEnabled,
    vapidPublicKey: vapidPublicKey,
    busy: false,
    message: '',
    error: false,

    async onToggle() {
      const csrf = window.READNEST_CSRF_TOKEN || '';
      this.busy = true;
      this.message = '';
      this.error = false;

      try {
        if (this.enabled) {
          await this.enable(csrf);
        } else {
          await this.disable(csrf);
        }
      } catch (e) {
        this.error = true;
        this.message = e.message || '操作に失敗しました';
        // ロールバック（UIと実状態を一致させる）
        this.enabled = !this.enabled;
      } finally {
        this.busy = false;
      }
    },

    async enable(csrf) {
      if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
        throw new Error('このブラウザはpush通知に対応していません');
      }
      if (!this.vapidPublicKey) {
        throw new Error('サーバー側の設定が未完了です');
      }

      const permission = await Notification.requestPermission();
      if (permission !== 'granted') {
        throw new Error('通知が許可されませんでした');
      }

      const reg = await getOrRegisterSW();
      if (!reg) throw new Error('Service Worker登録に失敗しました');

      let sub = await reg.pushManager.getSubscription();
      if (!sub) {
        sub = await reg.pushManager.subscribe({
          userVisibleOnly: true,
          applicationServerKey: urlBase64ToUint8Array(this.vapidPublicKey),
        });
      }

      const subJson = sub.toJSON();
      const subscribeRes = await postJson('/api/push_subscribe.php', {
        csrf_token: csrf,
        endpoint: subJson.endpoint,
        p256dh: subJson.keys.p256dh,
        auth: subJson.keys.auth,
      });
      if (!subscribeRes.success) throw new Error(subscribeRes.message || '購読登録に失敗しました');

      const settingsRes = await postJson('/api/notification_settings.php', {
        csrf_token: csrf,
        streak_reminder_enabled: 1,
      });
      if (!settingsRes.success) throw new Error(settingsRes.message || '設定の保存に失敗しました');

      this.message = '通知を有効にしました';
    },

    async disable(csrf) {
      const settingsRes = await postJson('/api/notification_settings.php', {
        csrf_token: csrf,
        streak_reminder_enabled: 0,
      });
      if (!settingsRes.success) throw new Error(settingsRes.message || '設定の保存に失敗しました');

      // ブラウザの購読も解除（同一ブラウザのみ。他デバイスはサーバ側に残る）
      try {
        const reg = await navigator.serviceWorker.getRegistration('/');
        if (reg) {
          const sub = await reg.pushManager.getSubscription();
          if (sub) {
            const endpoint = sub.endpoint;
            await sub.unsubscribe();
            await postJson('/api/push_unsubscribe.php', {
              csrf_token: csrf,
              endpoint: endpoint,
            }).catch(() => {});
          }
        }
      } catch (e) {
        // 失敗しても設定OFFは成功しているので致命ではない
      }

      this.message = '通知を無効にしました';
    },
  };
}

// グローバルに露出（x-data="streakReminderToggle(...)" から直接呼べるように）
window.streakReminderToggle = buildStreakReminderToggle;

// Alpine.js への登録（こちらが優先される）
function registerStreakReminderToggle() {
  if (window.Alpine && typeof window.Alpine.data === 'function') {
    window.Alpine.data('streakReminderToggle', buildStreakReminderToggle);
  }
}

if (window.Alpine) {
  registerStreakReminderToggle();
} else {
  document.addEventListener('alpine:init', registerStreakReminderToggle);
}
