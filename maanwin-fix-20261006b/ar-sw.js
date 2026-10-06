/**
 * maanwin — Service Worker DISABLED (cache-poison recovery build)
 * 2026-10-06
 *
 * WHY THIS FILE IS NOW A NO-OP
 * ----------------------------
 * The previous root service worker cached images with
 * stale-while-revalidate:
 *
 *     return cached || fetchPromise;   // always served the cached copy
 *
 * The first time the site loaded while an image was missing or truncated
 * on the server, that bad response was cached (it was an HTTP 200, so it
 * passed `response.ok`). From then on the service worker replayed the
 * broken copy from CacheStorage forever — re-uploading the correct file
 * to the server changed nothing.
 *
 * This replacement does three things and then removes itself:
 *   1. deletes every CacheStorage entry (ar-pwa*, online-page, …)
 *   2. unregisters itself
 *   3. never intercepts a single request again
 *
 * It is safe to keep on the server: once unregistered, the browser stops
 * asking for it. Offline support is intentionally traded for correctness.
 */

const CACHE_PREFIXES = ['ar-pwa', 'online-page'];

self.addEventListener('install', () => {
  // Take over immediately, do not wait for old tabs to close.
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    (async () => {
      // 1. wipe every cache we (or the old worker) ever created
      try {
        const keys = await caches.keys();
        await Promise.all(
          keys
            .filter((k) => CACHE_PREFIXES.some((p) => k.startsWith(p)))
            .map((k) => caches.delete(k))
        );
      } catch (e) {
        /* CacheStorage unavailable — nothing to clean */
      }

      // 2. reclaim clients and ask open tabs to reload on the network
      try {
        await self.clients.claim();
        const clients = await self.clients.matchAll({ type: 'window' });
        clients.forEach((c) => c.navigate(c.url));
      } catch (e) {
        /* navigation is best-effort */
      }

      // 3. remove this worker so it can never intercept again
      try {
        await self.registration.unregister();
      } catch (e) {
        /* unregister is best-effort */
      }
    })()
  );
});

self.addEventListener('message', (event) => {
  const { type } = event.data || {};
  if (type === 'SKIP_WAITING') self.skipWaiting();
  if (type === 'CLEAR_ALL_CACHE') {
    caches.keys().then((keys) =>
      keys
        .filter((k) => CACHE_PREFIXES.some((p) => k.startsWith(p)))
        .forEach((k) => caches.delete(k))
    );
  }
});

// Intentionally NO 'fetch' listener: every request goes straight to the
// network / normal browser cache.
