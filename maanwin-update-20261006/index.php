<?php
require_once __DIR__ . '/api/_core/config.php';
require_once __DIR__ . '/api/_core/bootstrap.php';

// ---- WebView / iframe fix ----------------------------------------------
// X-Frame-Options: SAMEORIGIN ki wajah se page app ke andar load nahi hota.
// Usko hata kar CSP frame-ancestors * bhejo (modern browser isi ko maante hain
// aur ye X-Frame-Options ko override kar deta hai).
header_remove('X-Frame-Options');
header_remove('Content-Security-Policy');
header_remove('Cross-Origin-Resource-Policy');
// -----------------------------------------------------------------------

$file = __DIR__ . '/index.html';
header('Content-Type: text/html; charset=utf-8');

// Game history /WinGo/*.json se aati hai, jo kuch servers par 404 ho jaati hai.
// Agar index.html me route-fix nahi hai (purani file upload ho), to yahan se
// inject kar do — tab bhi game history chal jayegi.
$html = @file_get_contents($file);
if ($html !== false && stripos($html, 'maanwin-route-fix') === false) {
    $patchJs = @is_file(__DIR__ . '/js/api-route-fix.js')
        ? (string) file_get_contents(__DIR__ . '/js/api-route-fix.js')
        : '';
    if ($patchJs === '') {
        $patchJs = "(function(){var C='WinGo|TrxWinGo|K3|D5|5D|MotoRace|MotoRacing';"
            . "var H=new RegExp('^/(?:'+C+')/([A-Za-z0-9_]+)/GetHistoryIssuePage\\\\.json(?:\\?([\\s\\S]*))?$','i');"
            . "var I=new RegExp('^/(?:'+C+')/([A-Za-z0-9_]+)\\\\.json(?:\\?([\\s\\S]*))?$','i');"
            . "function rp(u){if(typeof u!=='string'||!u)return u;"
            . "if(/^(https?:)?\\/\\//i.test(u)){try{var x=new URL(u,location.href);"
            . "if(x.origin!==location.origin)return u;u=x.pathname+(x.search||'');}catch(e){return u;}}"
            . "var m=H.exec(u);if(m){var q=m[2]||'';if(q.indexOf('pageNo')<0)q+=(q?'&':'')+'pageNo=1';"
            . "if(q.indexOf('pageSize')<0)q+=(q?'&':'')+'pageSize=10';"
            . "return '/api/Lottery/GetHistoryIssuePage?gameCode='+encodeURIComponent(m[1])+(q?'&'+q:'');}"
            . "m=I.exec(u);if(m){return '/api/Lottery/GetGameIssue?gameCode='+encodeURIComponent(m[1])+((m[2]||'')?'&'+m[2]:'');}"
            . "return u;}"
            . "if(typeof window.fetch==='function'){var f=window.fetch;window.fetch=function(i,n){try{"
            . "if(typeof i==='string')i=rp(i);else if(i&&typeof Request!=='undefined'&&i instanceof Request){"
            . "var u2=rp(i.url);if(u2!==i.url)return f.call(window,new Request(u2,i),n);}"
            . "else if(i&&typeof i.url==='string'){try{i.url=rp(i.url);}catch(e){}}}catch(e){}return f.call(window,i,n);};}"
            . "if(typeof XMLHttpRequest!=='undefined'&&XMLHttpRequest.prototype){var o=XMLHttpRequest.prototype.open;"
            . "XMLHttpRequest.prototype.open=function(){var a=Array.prototype.slice.call(arguments);"
            . "try{a[1]=rp(a[1]);}catch(e){}return o.apply(this,a);};}})();";
    }
    $tag = '<script data-maanwin-route-fix>' . $patchJs . '</script>';
    $count = 0;
    $html = preg_replace('/(<script[^>]+type="module"[^>]*>)/i', $tag . "\n" . '$1', $html, 1, $count);
    if (!$count) {
        $html = str_ireplace('</head>', $tag . '</head>', $html);
    }
}
echo $html;
?>