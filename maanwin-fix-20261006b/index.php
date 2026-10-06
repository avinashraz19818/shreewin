<?php
/**
 * maanwin — index.php
 * 2026-10-06
 *
 * Changes vs the original:
 *   1. X-Frame-Options / Content-Security-Policy / Cross-Origin-Resource-Policy
 *      are stripped so the site still embeds inside the Android WebView.
 *   2. Injects a "cache purge" script into the served HTML that unregisters
 *      every service worker and wipes CacheStorage. The old root service
 *      worker served images with stale-while-revalidate
 *      (`return cached || fetchPromise`), so a single bad/empty response
 *      cached once was replayed forever and no re-upload was ever visible.
 *      This makes every page load start from a clean cache.
 *
 * Database is never touched.
 */

require_once __DIR__ . '/api/_core/config.php';
require_once __DIR__ . '/api/_core/bootstrap.php';

// Embedding / iframe support: drop the headers that block the app WebView.
header_remove('X-Frame-Options');
header_remove('Content-Security-Policy');
header_remove('Cross-Origin-Resource-Policy');

// NEVER let this entry point be cached by a proxy or a service worker.
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

header('Content-Type: text/html; charset=utf-8');

$file = __DIR__ . '/index.html';
$html = @file_get_contents($file);

if ($html === false) {
    http_response_code(500);
    echo 'index.html not found';
    exit;
}

/**
 * Runs once per browser (flagged in localStorage) and then on every
 * boot that still finds a registered service worker or a non-empty
 * CacheStorage entry. Safe to leave in place permanently.
 */
$purge = <<<'HTML'
<script>
(function () {
  var FLAG = 'maanwin_cache_purge_v1';
  try {
    var done = window.localStorage && localStorage.getItem(FLAG) === 'done';
    var sw = navigator.serviceWorker;
    var hasCaches = !!window.caches;

    if (!sw && !hasCaches) { return; }

    if (done && sw && sw.controller === null && !sw.getRegistrations) { return; }

    var jobs = [];

    if (sw && sw.getRegistrations) {
      jobs.push(sw.getRegistrations().then(function (regs) {
        return Promise.all(regs.map(function (r) { return r.unregister(); }));
      }).catch(function () {}));
    }

    if (hasCaches && caches.keys) {
      jobs.push(caches.keys().then(function (keys) {
        return Promise.all(keys.map(function (k) { return caches.delete(k); }));
      }).catch(function () {}));
    }

    Promise.all(jobs).then(function () {
      try { localStorage.setItem(FLAG, 'done'); } catch (e) {}
      // Only reload if we actually cleared something on this pass.
      if (!done) { window.location.reload(true); }
    });
  } catch (e) { /* storage blocked — page still renders */ }
})();
</script>
HTML;

// Inject right after <head> so it runs before the SPA boots.
$count = 0;
$html = preg_replace('/<head([^>]*)>/i', '<head$1>' . $purge, $html, 1, $count);
if ($count === 0) {
    // No <head> found — put it straight after <html> as a fallback.
    $html = preg_replace('/<html([^>]*)>/i', '<html$1>' . $purge, $html, 1);
}

echo $html;
