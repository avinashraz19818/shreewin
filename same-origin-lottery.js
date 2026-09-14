/**
 * VeerGame same-origin lottery bridge.
 *
 * Why this file exists
 * --------------------
 * The compiled VeerGame client normally derives its backend hosts from the
 * lottery game URL:
 *
 *     ar_api      = api.<root-domain>      (bets / balance / game list)
 *     ar_api_json = draw.<root-domain>     (result feed + history)
 *
 * Those two subdomains only exist on the server the build was originally
 * compiled for. On any other deployment (a fresh cPanel account, a new domain,
 * a staging copy) every lottery request goes to a host that does not resolve,
 * so WinGo / K3 / 5D / TrxWinGo / MotoRace open but never load results, never
 * show the countdown and never accept a bet.
 *
 * This script runs before the application bundle and:
 *   0. hides the "Follow Strategy" tab (localStorage isOpenFollow = false),
 *   1. pins localStorage ar_api / ar_api_json to the current origin,
 *   2. rewrites any api.* / draw.* request to the current origin at runtime
 *      (fetch + XMLHttpRequest), covering stale cached bundles as well,
 *   3. maps the compiled API paths onto the endpoints that ship in this
 *      deployment (/api-live-v4 and /draw-live-v4),
 *   4. shows a readable message instead of a blank screen if a lazily loaded
 *      page chunk is missing from the upload.
 *
 * It is defensive on purpose: any error inside the bridge must never stop the
 * application from booting.
 */
(function () {
  "use strict";

  var origin = window.location.origin;
  var hostname = (window.location.hostname || "").toLowerCase();
  // Root domain suffix, used for api.* / draw.* host detection.
  var labels = hostname.split(".");
  var rootSuffix = labels.length >= 2 ? labels.slice(-2).join(".") : hostname;

  // Backends the compiled bundle was built against. Anything under these hosts
  // is a lottery/draw call, never a real third party service.
  var LEGACY_BACKEND_HOSTS = [
    "api.veergameapi.com",
    "h5.veergameapi.com",
    "api.club9.eu.cc",
    "draw.club9.eu.cc",
    "h5.ar-lottery06.com",
    "draw.ar-lottery06.com",
    "h5.ar-lottery01.com",
    "draw.ar-lottery01.com"
  ];

  var forcedValues = {
    ar_api: origin,
    ar_api_json: origin,
    // The lottery pages render the "Follow Strategy" tab only when this stored
    // flag is true. Forcing it to false removes the tab, also for browsers that
    // still carry the older value; the backend flag is turned off as well.
    isOpenFollow: false
  };

  function isBackendHost(host) {
    host = String(host || "").toLowerCase();
    if (!host) return false;
    // The current host, or any subdomain of it (api.<site>, draw.<site>).
    if (host === hostname || (hostname && host.slice(-(hostname.length + 1)) === "." + hostname)) {
      return true;
    }
    // api.<root-domain> / draw.<root-domain> of the browsed domain.
    if (host === "api." + rootSuffix || host === "draw." + rootSuffix) return true;
    for (var i = 0; i < LEGACY_BACKEND_HOSTS.length; i++) {
      var known = LEGACY_BACKEND_HOSTS[i];
      if (host === known || host.slice(-(known.length + 1)) === "." + known) return true;
    }
    return false;
  }

  // ---------------------------------------------------------------------------
  // 1) Persist the same-origin backends for the compiled client.
  // ---------------------------------------------------------------------------
  try {
    var rawSetItem = Storage.prototype.setItem;
    var wrapValue = function (key, value) {
      var parsed = null;
      try {
        parsed = JSON.parse(String(value));
      } catch (_) {
        parsed = null;
      }
      if (parsed && typeof parsed === "object" && !Array.isArray(parsed)) {
        parsed.value = forcedValues[key];
        if (!Object.prototype.hasOwnProperty.call(parsed, "expires")) parsed.expires = -1;
        return JSON.stringify(parsed);
      }
      return JSON.stringify({ value: forcedValues[key], expires: -1 });
    };

    rawSetItem.call(localStorage, "ar_api", JSON.stringify({ value: forcedValues.ar_api, expires: -1 }));
    rawSetItem.call(localStorage, "ar_api_json", JSON.stringify({ value: forcedValues.ar_api_json, expires: -1 }));
    rawSetItem.call(localStorage, "isOpenFollow", JSON.stringify({ value: false, expires: -1 }));

    Storage.prototype.setItem = function (key, value) {
      var normalized = String(key);
      if (this === localStorage && Object.prototype.hasOwnProperty.call(forcedValues, normalized)) {
        value = wrapValue(normalized, value);
      }
      return rawSetItem.call(this, key, value);
    };

    // Reads are masked too: a value stored before this script existed can not
    // switch the hidden UI back on.
    var rawGetItem = Storage.prototype.getItem;
    Storage.prototype.getItem = function (key) {
      if (this === localStorage && String(key) === "isOpenFollow") {
        return JSON.stringify({ value: false, expires: -1 });
      }
      return rawGetItem.call(this, key);
    };
  } catch (_) {
    /* storage disabled: URL rewriting below still keeps the game working */
  }

  // ---------------------------------------------------------------------------
  // 2) Rewrite backend URLs to this deployment.
  // ---------------------------------------------------------------------------
  function rewriteUrl(input) {
    try {
      if (typeof input !== "string" && !(input instanceof URL)) return input;
      var url = new URL(String(input), origin);
      var hostLower = url.hostname.toLowerCase();
      var changed = false;
      // True when the request was aimed at one of the bundle's built-in
      // backends (a host that is not the domain the user is browsing).
      var offsiteBackend = hostLower !== hostname && isBackendHost(hostLower);

      // 1) api.<root> / draw.<root> / legacy hosts -> this deployment.
      if (offsiteBackend) {
        url.protocol = window.location.protocol;
        url.host = window.location.host;
        changed = true;
      }

      // 2) Webapi calls (login, wallet, VIP, recharge, ...) live under
      //    /evenvessis here. Only remapped for the old backend hosts: a
      //    same-origin /api/webapi path is left untouched.
      if (offsiteBackend && /^\/api\/webapi\//i.test(url.pathname) && !/^\/evenvessis\//i.test(url.pathname)) {
        url.pathname = "/developer-maruf" + url.pathname;
        changed = true;
      }

      var pathname = url.pathname;

      // 3) Lottery REST API -> the handler that ships in this deployment.
      //    /api/Lottery/<Action>, /api-live-v4/Lottery/<Action> and the
      //    legacy /Lottery/<Action> of the old API host.
      var apiMatch =
        pathname.match(/^\/api\/Lottery\/([A-Za-z0-9_-]+)(?:\.php)?\/?$/i) ||
        pathname.match(/^\/api-live-v4\/Lottery\/([A-Za-z0-9_-]+)(?:\.php)?\/?$/i) ||
        (offsiteBackend ? pathname.match(/^\/Lottery\/([A-Za-z0-9_-]+)(?:\.php)?\/?$/i) : null);
      if (apiMatch) {
        url.pathname = "/api-live-v4/Lottery/index.php";
        url.searchParams.set("action", apiMatch[1]);
        changed = true;
      }

      // 4) Public draw feed:
      //    /<Lottery>/<gameCode>.json and /<Lottery>/<gameCode>/GetHistoryIssuePage.json
      var historyMatch = pathname.match(/^\/(?:draw-live-v4\/)?(WinGo|TrxWinGo|K3|D5|MotoRace)\/([A-Za-z0-9_-]+)\/GetHistoryIssuePage\.json$/i);
      var drawMatch = pathname.match(/^\/(?:draw-live-v4\/)?(WinGo|TrxWinGo|K3|D5|MotoRace)\/([A-Za-z0-9_-]+)\.json$/i);
      if (historyMatch) {
        url.pathname = "/draw-live-v4/index.php";
        url.searchParams.set("lottery", historyMatch[1]);
        url.searchParams.set("gameCode", historyMatch[2]);
        url.searchParams.set("history", "1");
        changed = true;
      } else if (drawMatch) {
        url.pathname = "/draw-live-v4/index.php";
        url.searchParams.set("lottery", drawMatch[1]);
        url.searchParams.set("gameCode", drawMatch[2]);
        changed = true;
      }

      // Nothing to remap: return the original value so plain relative asset
      // URLs stay relative (and are not needlessly absolutised).
      if (!changed) return input;
      return url.href;
    } catch (_) {
      /* fall through: return the original value */
    }
    return input;
  }

  try {
    var rawOpen = XMLHttpRequest.prototype.open;
    XMLHttpRequest.prototype.open = function (method, url) {
      var args = Array.prototype.slice.call(arguments);
      args[1] = rewriteUrl(url);
      return rawOpen.apply(this, args);
    };
  } catch (_) {
    /* ignore */
  }

  try {
    if (window.fetch) {
      var rawFetch = window.fetch;
      window.fetch = function (input, init) {
        try {
          if (typeof input === "string" || input instanceof URL) {
            input = rewriteUrl(input);
          } else if (typeof Request !== "undefined" && input instanceof Request) {
            var rewritten = rewriteUrl(input.url);
            if (rewritten !== input.url) input = new Request(rewritten, input);
          }
        } catch (_) {
          /* ignore */
        }
        return rawFetch.call(this, input, init);
      };
    }
  } catch (_) {
    /* ignore */
  }

  // ---------------------------------------------------------------------------
  // 3) A missing lazy chunk must not leave the user on a blank screen.
  // ---------------------------------------------------------------------------
  var notified = false;
  function chunkFailure(reason) {
    var text = String(reason || "");
    if (!/dynamically imported module|Importing a module script failed|Loading chunk|Loading CSS chunk/i.test(text)) {
      return false;
    }
    if (notified) return true;
    notified = true;
    try {
      var box = document.createElement("div");
      box.setAttribute("role", "alert");
      box.style.cssText = "position:fixed;inset:auto 12px 12px;z-index:2147483647;background:#1f2937;color:#fff;" +
        "font:14px/1.5 system-ui,-apple-system,Segoe UI,Roboto,sans-serif;padding:14px 16px;border-radius:12px;" +
        "box-shadow:0 10px 30px rgba(0,0,0,.35);max-width:420px;margin:0 auto";
      box.textContent = "This page is still being uploaded to the server. Please refresh, or open the WinGo game from the home screen.";
      document.body.appendChild(box);
      setTimeout(function () {
        if (box.parentNode) box.parentNode.removeChild(box);
      }, 8000);
    } catch (_) {
      /* ignore */
    }
    return true;
  }

  try {
    if (typeof window.addEventListener === "function") {
      window.addEventListener("unhandledrejection", function (event) {
        if (chunkFailure(event && event.reason && (event.reason.message || event.reason))) {
          // Keep the rejection handled so the router does not stay on a
          // half-navigated (blank) route.
          event.preventDefault();
          try {
            if (window.location.hash && !/^#\/?$/.test(window.location.hash)) {
              window.location.hash = "#/";
            }
          } catch (_) {
            /* ignore */
          }
        }
      });
      window.addEventListener("error", function (event) {
        chunkFailure(event && (event.message || (event.error && event.error.message)));
      });
    }
  } catch (_) {
    /* ignore */
  }

  // ---------------------------------------------------------------------------
  // 4) Drop any older build's service worker / cache.
  //
  // A previously deployed bundle on this origin may still hold a service worker
  // that serves the old (broken) JavaScript and HTML from its cache, which
  // makes the fix look like it was not applied. Only the app's own worker is
  // touched; unrelated registrations (chat widgets, analytics) are left alone.
  // ---------------------------------------------------------------------------
  function retireOldWorkers() {
    try {
      if (!navigator.serviceWorker || typeof navigator.serviceWorker.getRegistrations !== "function") return;
      navigator.serviceWorker.getRegistrations().then(function (registrations) {
        registrations.forEach(function (registration) {
          var url = "";
          try {
            url = (registration.active && registration.active.scriptURL) ||
              (registration.installing && registration.installing.scriptURL) ||
              (registration.waiting && registration.waiting.scriptURL) || "";
          } catch (_) {
            url = "";
          }
          if (/\/ar-sw\.js(\?|$)/i.test(url) || /\/sw-page\.js(\?|$)/i.test(url) || /\/sw-domain\.js(\?|$)/i.test(url)) {
            registration.unregister().catch(function () {});
          }
        });
      }).catch(function () {});
    } catch (_) {
      /* ignore */
    }

    try {
      if (!window.caches || typeof window.caches.keys !== "function") return;
      window.caches.keys().then(function (keys) {
        keys.forEach(function (key) {
          if (/(?:^|[^a-z])(workbox|ar-sw|lottery|veergame|shreewin)(?:[^a-z]|$)/i.test(key)) {
            window.caches.delete(key).catch(function () {});
          }
        });
      }).catch(function () {});
    } catch (_) {
      /* ignore */
    }
  }

  try {
    if (!document || typeof document.addEventListener !== "function") {
      retireOldWorkers();
    } else if (document.readyState === "complete" || document.readyState === "interactive") {
      retireOldWorkers();
    } else {
      document.addEventListener("DOMContentLoaded", retireOldWorkers, { once: true });
    }
  } catch (_) {
    /* never let cache cleanup break the boot */
  }

  // Expose the resolver so the application (or support) can inspect it.
  window.VEERGAME_SAME_ORIGIN = {
    origin: origin,
    api: origin + "/api-live-v4",
    draw: origin + "/draw-live-v4",
    rewrite: rewriteUrl
  };
})();
