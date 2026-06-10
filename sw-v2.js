// ReadNest Service Worker
// 戦略:
//   - 静的アセット (CSS/JS/フォント/ロゴ等)  : Cache First
//   - 本の表紙画像                          : Stale While Revalidate
//   - API レスポンス (/api/, /ajax/)        : Network First → cache fallback
//   - HTML ナビゲーション                   : Network First → /offline.html
// 外部解析スクリプト (GTM/gtag/Tailwind CDN等) はキャッシュ対象外（passthrough）。

const VERSION = 'v1.5.0';

const STATIC_CACHE = `readnest-static-${VERSION}`;
const IMAGE_CACHE = `readnest-images-${VERSION}`;
const API_CACHE = `readnest-api-${VERSION}`;
const HTML_CACHE = `readnest-html-${VERSION}`;

const ALL_CACHES = [STATIC_CACHE, IMAGE_CACHE, API_CACHE, HTML_CACHE];

// インストール時にプリキャッシュする最小限の資産
const PRECACHE_URLS = [
  '/offline.html',
  '/manifest.json',
  '/img/logo.png',
  '/img/no-image-book.png',
  '/img/book-placeholder.svg',
];

// 表紙画像のホスト
const BOOK_IMAGE_HOSTS = [
  'images-na.ssl-images-amazon.com',
  'images-fe.ssl-images-amazon.com',
  'm.media-amazon.com',
  'books.google.com',
  'books.googleusercontent.com',
];

// キャッシュ対象外（解析・第三者スクリプト）
const BYPASS_HOSTS = [
  'www.googletagmanager.com',
  'www.google-analytics.com',
  'cdn.tailwindcss.com',
  'cdn.jsdelivr.net',
  'cdnjs.cloudflare.com',
];

// 画像キャッシュの上限（簡易LRU）
const IMAGE_CACHE_MAX_ENTRIES = 200;
const API_CACHE_MAX_ENTRIES = 50;
// HTMLキャッシュの上限。POST後リダイレクトのキャッシュバスティング(?_cb=...)で
// ユニークURLが増え続けるため上限を設けて古いものから破棄する。
const HTML_CACHE_MAX_ENTRIES = 60;

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(STATIC_CACHE)
      .then((cache) => cache.addAll(PRECACHE_URLS))
      .then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys()
      .then((keys) => Promise.all(
        keys.filter((key) => key.startsWith('readnest-') && !ALL_CACHES.includes(key))
            .map((key) => caches.delete(key))
      ))
      // Navigation Preload: SW 起動と並行してナビゲーションの取得を先行開始し、
      // 初回（キャッシュ無し）ページの体感速度を改善する
      .then(() => {
        if (self.registration.navigationPreload) {
          return self.registration.navigationPreload.enable();
        }
      })
      .then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (event) => {
  const request = event.request;

  // GET 以外は Service Worker を経由させない
  if (request.method !== 'GET') return;

  const url = new URL(request.url);

  // 第三者解析・CDN は素通し
  if (BYPASS_HOSTS.includes(url.hostname)) return;

  // chrome-extension など独自スキームは素通し
  if (url.protocol !== 'http:' && url.protocol !== 'https:') return;

  // manifest.json は SW でキャッシュせずブラウザ標準処理に任せる
  // （PWA インストール検証で常に最新を読ませるため）
  if (url.pathname === '/manifest.json') return;

  // 1. 本の表紙画像 (外部ホスト)
  if (BOOK_IMAGE_HOSTS.includes(url.hostname)) {
    event.respondWith(staleWhileRevalidate(request, IMAGE_CACHE, IMAGE_CACHE_MAX_ENTRIES));
    return;
  }

  // 同一オリジンのみ以下の戦略を適用
  if (url.origin !== self.location.origin) return;

  // 2. API / Ajax レスポンス
  if (url.pathname.startsWith('/api/') || url.pathname.startsWith('/ajax/')) {
    event.respondWith(networkFirst(request, API_CACHE, API_CACHE_MAX_ENTRIES));
    return;
  }

  // 3. HTML ナビゲーション
  if (request.mode === 'navigate' || (request.headers.get('accept') || '').includes('text/html')) {
    event.respondWith(navigationHandler(event));
    return;
  }

  // 4. 静的アセット
  if (isStaticAsset(url.pathname)) {
    event.respondWith(cacheFirst(request, STATIC_CACHE));
    return;
  }

  // それ以外は同一オリジンの画像系として SWR
  if (/\.(png|jpe?g|gif|webp|svg|ico)$/i.test(url.pathname)) {
    event.respondWith(staleWhileRevalidate(request, IMAGE_CACHE, IMAGE_CACHE_MAX_ENTRIES));
    return;
  }
});

function isStaticAsset(pathname) {
  return /\.(css|js|woff2?|ttf|otf|eot)$/i.test(pathname)
      || pathname.startsWith('/css/')
      || pathname.startsWith('/js/')
      || pathname.startsWith('/fonts/')
      || pathname.startsWith('/template/modern/css/')
      || pathname.startsWith('/template/modern/js/')
      || pathname.startsWith('/template/modern/img/')
      || pathname === '/favicon.ico'
      || pathname === '/favicon-16x16.png'
      || pathname === '/favicon-32x32.png';
}

async function cacheFirst(request, cacheName) {
  const cache = await caches.open(cacheName);
  const cached = await cache.match(request);
  if (cached) return cached;
  try {
    const response = await fetch(request);
    if (response && response.status === 200 && response.type !== 'opaque') {
      cache.put(request, response.clone());
    }
    return response;
  } catch (err) {
    return cached || Response.error();
  }
}

async function networkFirst(request, cacheName, maxEntries) {
  const cache = await caches.open(cacheName);
  try {
    const response = await fetch(request);
    if (response && response.status === 200) {
      cache.put(request, response.clone()).then(() => trimCache(cacheName, maxEntries));
    }
    return response;
  } catch (err) {
    const cached = await cache.match(request);
    if (cached) return cached;
    throw err;
  }
}

async function staleWhileRevalidate(request, cacheName, maxEntries) {
  const cache = await caches.open(cacheName);
  const cached = await cache.match(request);
  const fetchPromise = fetch(request)
    .then((response) => {
      if (response && (response.status === 200 || response.type === 'opaque')) {
        cache.put(request, response.clone()).then(() => trimCache(cacheName, maxEntries));
      }
      return response;
    })
    .catch(() => cached);
  return cached || fetchPromise;
}

// ナビゲーション応答をキャッシュして良いか判定する。
// 過去の白画面の原因は、PHP fatal 等で途中で切れた「空・不完全なHTML」を
// 200 として保存し配信し続けていたこと。完全な HTML だけを保存対象にする。
async function isCompleteHtmlResponse(response) {
  // status 200 の自オリジン (basic) かつリダイレクト結果でないもののみ
  if (!response || response.status !== 200 || response.type !== 'basic' || response.redirected) {
    return false;
  }
  try {
    const text = await response.clone().text();
    // 終端タグがあれば完全なページとみなす（途中で切れた応答は弾く）
    return /<\/html\s*>/i.test(text);
  } catch (err) {
    return false;
  }
}

async function navigationHandler(event) {
  const request = event.request;
  const cache = await caches.open(HTML_CACHE);
  const cached = await cache.match(request);

  // 明示的なリロード（location.reload() は cache:'reload'、強制再読込は 'no-cache'）や
  // no-store 指定時は、古いキャッシュを返さずネットワークの最新応答を優先する。
  // → PWAで読書進捗を追加した直後の location.reload() で古いHTMLが表示され、
  //   「画面が更新されない」とユーザーが再送信して重複レコードが生じる問題への対策。
  const bypassCache = request.cache === 'reload'
    || request.cache === 'no-store'
    || request.cache === 'no-cache';

  // ネットワーク取得（Navigation Preload があれば再利用）＋ 完全なら裏でキャッシュ更新
  const networkUpdate = (async () => {
    try {
      const preload = await event.preloadResponse;
      const response = preload || await fetch(request);
      if (await isCompleteHtmlResponse(response)) {
        await cache.put(request, response.clone());
        await trimCache(HTML_CACHE, HTML_CACHE_MAX_ENTRIES);
      }
      return response;
    } catch (err) {
      return null;
    }
  })();

  // リロード時: ネットワークを待って最新を表示。失敗時のみキャッシュ→オフラインに退避。
  if (bypassCache) {
    const fresh = await networkUpdate;
    if (fresh) return fresh;
    if (cached) return cached;
    const offline = await caches.match('/offline.html');
    return offline || new Response('Offline', { status: 503, statusText: 'Offline' });
  }

  // 通常ナビゲーション: キャッシュがあれば即表示（SWR）。更新は裏で継続。
  // → モバイルでもタップ直後に前回の内容が瞬時に出るため白画面が出ない。
  if (cached) {
    event.waitUntil(networkUpdate);
    return cached;
  }

  // 初回（キャッシュ無し）: ネットワークを待つ。失敗時はオフラインページ。
  const response = await networkUpdate;
  if (response) return response;
  const offline = await caches.match('/offline.html');
  return offline || new Response('Offline', { status: 503, statusText: 'Offline' });
}

async function trimCache(cacheName, maxEntries) {
  if (!maxEntries) return;
  const cache = await caches.open(cacheName);
  const keys = await cache.keys();
  if (keys.length <= maxEntries) return;
  const excess = keys.length - maxEntries;
  for (let i = 0; i < excess; i++) {
    await cache.delete(keys[i]);
  }
}

// クライアントから skipWaiting を要求された場合
self.addEventListener('message', (event) => {
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
});

// Push 通知の受信
self.addEventListener('push', (event) => {
  let payload = {};
  try {
    payload = event.data ? event.data.json() : {};
  } catch (e) {
    payload = { title: 'ReadNest', body: event.data ? event.data.text() : '' };
  }

  const title = payload.title || 'ReadNest';
  const options = {
    body: payload.body || '',
    icon: payload.icon || '/img/logo.png',
    badge: payload.badge || '/img/logo.png',
    tag: payload.tag || 'readnest-notification',
    data: { url: payload.url || '/' },
    requireInteraction: false,
  };

  event.waitUntil(self.registration.showNotification(title, options));
});

// 通知クリック時の挙動: 既存タブにフォーカス、無ければ新規で開く
self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  const targetUrl = (event.notification.data && event.notification.data.url) || '/';

  event.waitUntil(
    self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
      for (const client of clientList) {
        if ('focus' in client) {
          client.navigate(targetUrl).catch(() => {});
          return client.focus();
        }
      }
      return self.clients.openWindow(targetUrl);
    })
  );
});
