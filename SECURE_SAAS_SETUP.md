# Secure owner console setup

1. Upload the complete package contents to the target `public_html` directory.
2. Open and sign in to `/digitaladmin/` first.
3. In the same browser, open `https://shreewin.club9.eu.cc/saas.php`.
4. Create a strong owner password of at least 12 characters. The console does
   not ship with a default or plain-text password.
5. Use **Game activation** to enable or disable each WinGo, K3, 5D, TRX and
   Moto Racing interval. These switches update the SQL controls consumed by
   the live game API.

The page responds with HTTP 404 on hosts other than `shreewin.club9.eu.cc` and
`www.shreewin.club9.eu.cc`. It is not linked in normal navigation. Login attempts
are rate-limited, sessions expire after 20 minutes, every mutation is protected
with CSRF tokens, and owner actions are written to `saas_owner_audit`.

All required owner-console, game-control and site-message tables are created
automatically by `admin_runtime_schema.php`. Schema changes are additive and
do not drop existing data.

The console intentionally has no arbitrary PHP/file editor and no hidden
cross-domain command channel. Website notices are stored as escaped text and
are returned through the normal `GetSitePopMsgList` API.
