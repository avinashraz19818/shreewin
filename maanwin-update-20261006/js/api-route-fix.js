/**
 * maanwin — lottery route fix  (runs before the app bundle)
 *
 * Kuch servers .htaccess ke is rule ko nahi maante:
 *     RewriteRule ^(WinGo|K3|D5|MotoRace|TrxWinGo)/(.+\.json)$ api/_draw_router.php?path=$1/$2
 * Is wajah se  /WinGo/WinGo_30S/GetHistoryIssuePage.json  404 ho jaata hai aur
 * WinGo page ka "Game History" khaali rehta hai.
 *
 * Ye file browser me hi un do URL ko /api/ wale (kaam karne wale) route par
 * mod deti hai. Server config me kuch badalne ki zarurat nahi.
 *
 *   /WinGo/WinGo_30S/GetHistoryIssuePage.json -> /api/Lottery/GetHistoryIssuePage?gameCode=WinGo_30S
 *   /WinGo/WinGo_30S.json                     -> /api/Lottery/GetGameIssue?gameCode=WinGo_30S
 *
 * Dono ka jawab bilkul wahi shape ka hota hai, isliye app ko koi farak nahi padta.
 */
(function () {
    'use strict';
    if (window.__MAANWIN_ROUTE_FIX__) { return; }
    window.__MAANWIN_ROUTE_FIX__ = true;

    var CAT = 'WinGo|TrxWinGo|K3|D5|5D|MotoRace|MotoRacing';
    var RE_HISTORY = new RegExp('^/(?:' + CAT + ')/([A-Za-z0-9_]+)/GetHistoryIssuePage\\.json(?:\\?([\\s\\S]*))?$', 'i');
    var RE_ISSUE = new RegExp('^/(?:' + CAT + ')/([A-Za-z0-9_]+)\\.json(?:\\?([\\s\\S]*))?$', 'i');

    function sameOriginUrl(url) {
        // absolute URL ho to uska pathname+search nikaalo (sirf apne hi domain ka)
        if (!/^(https?:)?\/\//i.test(url)) { return url; }
        try {
            var u = new URL(url, window.location.href);
            if (u.origin !== window.location.origin) { return null; }
            return u.pathname + (u.search || '');
        } catch (e) { return null; }
    }

    function rewrite(url) {
        if (typeof url !== 'string' || url === '') { return url; }
        var path = sameOriginUrl(url);
        if (path === null) { return url; }

        var m = RE_HISTORY.exec(path);
        if (m) {
            var q = m[2] || '';
            if (q.indexOf('pageNo') < 0) { q += (q ? '&' : '') + 'pageNo=1'; }
            if (q.indexOf('pageSize') < 0) { q += (q ? '&' : '') + 'pageSize=10'; }
            return '/api/Lottery/GetHistoryIssuePage?gameCode=' + encodeURIComponent(m[1]) + (q ? '&' + q : '');
        }

        m = RE_ISSUE.exec(path);
        if (m) {
            var qi = m[2] || '';
            return '/api/Lottery/GetGameIssue?gameCode=' + encodeURIComponent(m[1]) + (qi ? '&' + qi : '');
        }

        return url;
    }

    // 1) fetch()
    if (typeof window.fetch === 'function') {
        var rawFetch = window.fetch;
        window.fetch = function (input, init) {
            try {
                if (typeof input === 'string') {
                    input = rewrite(input);
                } else if (input && typeof Request !== 'undefined' && input instanceof Request) {
                    var next = rewrite(input.url);
                    if (next !== input.url) {
                        return rawFetch.call(window, new Request(next, input), init);
                    }
                } else if (input && typeof input.url === 'string') {
                    try { input.url = rewrite(input.url); } catch (e) {}
                }
            } catch (e) {}
            return rawFetch.call(window, input, init);
        };
    }

    // 2) XMLHttpRequest  (axios isi par chalta hai)
    if (typeof XMLHttpRequest !== 'undefined' && XMLHttpRequest.prototype) {
        var rawOpen = XMLHttpRequest.prototype.open;
        XMLHttpRequest.prototype.open = function () {
            var args = Array.prototype.slice.call(arguments);
            try { args[1] = rewrite(args[1]); } catch (e) {}
            return rawOpen.apply(this, args);
        };
    }
})();
