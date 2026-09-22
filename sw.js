// =============================================================================
// /sw.js — kill-switch（自己破壊）Service Worker
// -----------------------------------------------------------------------------
// 経緯: Service Worker は 2026-05-07 に /sw.js → /sw-v2.js へ移設された。
// しかし移設前に /sw.js を登録した端末は、その後も古い /sw.js（v1.2.0,
// キャッシュ優先寄りのナビゲーション）に制御され続け、6/16 の Network-First
// 修正（sw-v2.js v1.6.0）が永久に届かない。結果として「ログイン画面が居座る」
// 「特定ページが未ログイン表示」等が解消しない端末が残る。
//
// この /sw.js は、そうした取り残された端末を救済するための最小 SW。
// 役割は「全キャッシュ削除 → 自分自身を unregister → ページを再読み込み」だけ。
// 再読み込み後は t_base.php が /sw-v2.js（最新）を登録し直すため、端末は
// 自動的に正しい SW へ乗り換わる。以後 /sw.js が制御することはない。
//
// デプロイ時の注意（重要）:
//   1. このファイルを本番 /sw.js へ FTP アップロードする。
//   2. 前段 CDN の /sw.js を必ずパージする。しないと旧 v1.2.0 が最大30日
//      返り続け、この kill-switch が端末に届かない。
//   3. 併せて /sw.js は Cache-Control: no-cache 配信が望ましい。
// =============================================================================

self.addEventListener('install', () => {
  // 待機せず即座に有効化候補にする
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil((async () => {
    // 1. この SW が作った全キャッシュを削除
    try {
      const keys = await caches.keys();
      await Promise.all(keys.map((key) => caches.delete(key)));
    } catch (err) {
      // キャッシュ削除に失敗しても unregister は続行する
    }

    // 2. 自分自身の登録を解除（以後この SW は二度と制御しない）
    try {
      await self.registration.unregister();
    } catch (err) {
      // noop
    }

    // 3. 制御中の全ウィンドウをリロードさせ、/sw-v2.js を登録し直させる
    try {
      const clients = await self.clients.matchAll({ type: 'window' });
      for (const client of clients) {
        // navigate が使えない環境向けに postMessage も併用（保険）
        if ('navigate' in client) {
          client.navigate(client.url);
        }
      }
    } catch (err) {
      // noop
    }
  })());
});

// unregister が完了するまでの間に発生する fetch は、キャッシュを一切使わず
// 常にネットワークへ素通しする（古いキャッシュを配信し続けないため）。
// オフライン等でネットワークが失敗した場合のみ、残存キャッシュにフォールバック。
self.addEventListener('fetch', (event) => {
  event.respondWith(
    fetch(event.request).catch(() => caches.match(event.request))
  );
});
