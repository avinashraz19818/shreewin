(function () {
    'use strict';

    var recoveryKey = 'shreewin_home_recovery_v3';
    var authCodes = { 4: true, 22: true };
    var tokenText = /token has expired|login has expired|logged in elsewhere/i;

    function hasStoredSession() {
        return Boolean(
            localStorage.getItem('token') ||
            localStorage.getItem('refreshToken') ||
            localStorage.getItem('tokenHeader')
        );
    }

    function clearStoredSession() {
        [
            'token',
            'refreshToken',
            'tokenHeader',
            'ar_token',
            'lotteryLoginUrl',
            'firstSave',
            'isToLogin'
        ].forEach(function (key) {
            localStorage.removeItem(key);
        });
        sessionStorage.removeItem('permission');
        sessionStorage.removeItem('ar_pay');
    }

    function recoverHome() {
        var now = Date.now();
        var previous = Number(sessionStorage.getItem(recoveryKey) || 0);
        if (now - previous < 15000) return;

        sessionStorage.setItem(recoveryKey, String(now));
        clearStoredSession();

        window.setTimeout(function () {
            var cleanHome = window.location.pathname + window.location.search + '#/';
            window.location.replace(cleanHome);
            window.location.reload();
        }, 30);
    }

    function inspectPayload(payload, status) {
        var code = Number(
            payload && (payload.code !== undefined ? payload.code : payload.msgCode)
        );
        if ((status === 401 || authCodes[code]) && hasStoredSession()) {
            recoverHome();
        }
    }

    if (window.XMLHttpRequest) {
        var nativeOpen = window.XMLHttpRequest.prototype.open;
        window.XMLHttpRequest.prototype.open = function () {
            this.addEventListener('load', function () {
                var payload = null;
                try {
                    payload = JSON.parse(this.responseText || '{}');
                } catch (ignore) {}
                inspectPayload(payload, Number(this.status || 0));
            });
            return nativeOpen.apply(this, arguments);
        };
    }

    if (window.fetch) {
        var nativeFetch = window.fetch;
        window.fetch = function () {
            return nativeFetch.apply(this, arguments).then(function (response) {
                response.clone().json().then(function (payload) {
                    inspectPayload(payload, response.status);
                }).catch(function () {
                    inspectPayload(null, response.status);
                });
                return response;
            });
        };
    }

    function watchExpiredMessage() {
        if (!document.documentElement) return;
        var observer = new MutationObserver(function () {
            var text = document.body ? document.body.textContent : '';
            if (tokenText.test(text)) recoverHome();
        });
        observer.observe(document.documentElement, {
            childList: true,
            subtree: true,
            characterData: true
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', watchExpiredMessage, { once: true });
    } else {
        watchExpiredMessage();
    }
}());
