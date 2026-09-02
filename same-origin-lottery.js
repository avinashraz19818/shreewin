(function () {
  "use strict";

  var origin = window.location.origin;
  var apiBase = origin + "/api-live-v4";
  var drawBase = origin + "/draw-live-v4";
  var forcedValues = { ar_api: apiBase, ar_api_json: drawBase };
  var rawSetItem = Storage.prototype.setItem;

  function wrappedValue(key, value) {
    var parsed;
    try {
      parsed = JSON.parse(String(value));
    } catch (_) {
      parsed = null;
    }
    if (parsed && typeof parsed === "object" && !Array.isArray(parsed) && Object.prototype.hasOwnProperty.call(parsed, "value")) {
      parsed.value = forcedValues[key];
      if (!Object.prototype.hasOwnProperty.call(parsed, "expires")) parsed.expires = -1;
      return JSON.stringify(parsed);
    }
    return JSON.stringify({ value: forcedValues[key], expires: -1 });
  }

  try {
    rawSetItem.call(localStorage, "ar_api", JSON.stringify({ value: apiBase, expires: -1 }));
    rawSetItem.call(localStorage, "ar_api_json", JSON.stringify({ value: drawBase, expires: -1 }));
  } catch (_) {}

  try {
    Storage.prototype.setItem = function (key, value) {
      var normalizedKey = String(key);
      if (this === localStorage && Object.prototype.hasOwnProperty.call(forcedValues, normalizedKey)) {
        value = wrappedValue(normalizedKey, value);
      }
      return rawSetItem.call(this, key, value);
    };
  } catch (_) {}

  function rewriteUrl(input) {
    try {
      var u = new URL(String(input), origin);
      var host = u.hostname.toLowerCase();
      var current = window.location.hostname.toLowerCase();
      var parts = current.split(".");
      var suffix = parts.length >= 2 ? parts.slice(-2).join(".") : current;
      var apiHost = (
        host === "api." + suffix ||
        host === "api.55ak.xyz" ||
        host === "h5.ar-lottery06.com"
      );
      var drawHost = (
        host === "draw." + suffix ||
        host === "draw.55ak.xyz" ||
        host === "draw.ar-lottery06.com"
      );
      var changed = false;

      if (apiHost || drawHost) {
        u.protocol = window.location.protocol;
        u.host = window.location.host;
        changed = true;
      }

      var apiMatch = u.pathname.match(/^\/(?:api-live-v4\/)?api\/Lottery\/([A-Za-z0-9_-]+)\/?$/i) ||
        u.pathname.match(/^\/api-live-v4\/Lottery\/([A-Za-z0-9_-]+)\/?$/i) ||
        (apiHost ? u.pathname.match(/^\/Lottery\/([A-Za-z0-9_-]+)\/?$/i) : null);
      if (apiMatch) {
        u.pathname = "/api-live-v4/Lottery/index.php";
        u.searchParams.set("action", apiMatch[1]);
        changed = true;
      }

      var drawMatch = u.pathname.match(/^\/(?:draw-live-v4\/)?(WinGo|TrxWinGo|K3|D5|MotoRace)\/([A-Za-z0-9_-]+)\.json$/i);
      var historyMatch = u.pathname.match(/^\/(?:draw-live-v4\/)?(WinGo|TrxWinGo|K3|D5|MotoRace)\/([A-Za-z0-9_-]+)\/GetHistoryIssuePage\.json$/i);
      if (historyMatch) {
        u.pathname = "/draw-live-v4/index.php";
        u.searchParams.set("lottery", historyMatch[1]);
        u.searchParams.set("gameCode", historyMatch[2]);
        u.searchParams.set("history", "1");
        changed = true;
      } else if (drawMatch) {
        u.pathname = "/draw-live-v4/index.php";
        u.searchParams.set("lottery", drawMatch[1]);
        u.searchParams.set("gameCode", drawMatch[2]);
        changed = true;
      }

      if (changed) return u.href;
    } catch (_) {}
    return input;
  }

  try {
    var rawOpen = XMLHttpRequest.prototype.open;
    XMLHttpRequest.prototype.open = function (method, url) {
      var args = Array.prototype.slice.call(arguments);
      args[1] = rewriteUrl(url);
      return rawOpen.apply(this, args);
    };
  } catch (_) {}

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
        } catch (_) {}
        return rawFetch.call(this, input, init);
      };
    }
  } catch (_) {}
})();
