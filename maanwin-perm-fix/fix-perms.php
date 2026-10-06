<?php
/**
 * fix-perms.php — folder permission fixer
 * =======================================
 * Zip extract karte waqt "Permission denied / cannot create <folder>"
 * isliye aata hai kyunki folder writable nahi hote.
 *
 *   - existing file overwrite ho jati hai (folder-write zaroori nahi)
 *   - par NAYA folder/file create nahi ho sakta  -> error
 *
 * Ye script saare folders ko 0755 aur files ko 0644 karta hai,
 * aur kuch khaas folders (helpers, webapi, pay/logs, api) ko 0775
 * taaki PHP likh sake.
 *
 * CHALANE KA TARIKA:
 *   1. Is file ko public_html me upload karo
 *   2. Browser me kholo:  https://maanwin.club9.eu.cc/fix-perms.php
 *   3. "DONE" dikhe to is file ko DELETE kar dena
 *   4. Ab apna zip DOBARA extract karo
 */

header('Content-Type: text/plain; charset=utf-8');
set_time_limit(0);

$ROOT = __DIR__;

// Ye folders PHP ko likhne hote hain -> 0775
$WRITABLE = [
    'helpers',
    'webapi',
    'webapi/kv',
    'webapi/kv/issue',
    'api',
    'api/_core',
    'pay',
    'pay/logs',
    'pay/logs/cashier',
];

function is_writable_dir($rel, array $writable) {
    foreach ($writable as $w) {
        if ($rel === $w || strpos($rel . '/', $w . '/') === 0) {
            return true;
        }
    }
    return false;
}

$dirs = 0;
$files = 0;
$failed = [];

// Pehle saare folders ko 0777 bana do (taaki andar kaam ho sake),
// phir end me sahi permission set karenge
$allDirs = [];
$allFiles = [];

$rii = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($ROOT, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

foreach ($rii as $path => $info) {
    // khud ko chhod do
    if (realpath($path) === realpath(__FILE__)) {
        continue;
    }
    if ($info->isDir()) {
        $allDirs[] = $path;
    } else {
        $allFiles[] = $path;
    }
}

// 1) folders: pehle 0777
foreach ($allDirs as $d) {
    if (!@chmod($d, 0777)) {
        $failed[] = 'DIR  ' . str_replace($ROOT . '/', '', $d);
    } else {
        $dirs++;
    }
}

// 2) files: 0666 (taaki overwrite ho sake)
foreach ($allFiles as $f) {
    if (!@chmod($f, 0666)) {
        $failed[] = 'FILE ' . str_replace($ROOT . '/', '', $f);
    } else {
        $files++;
    }
}

clearstatcache();

// 3) ab final permissions: folders 0755 (ya 0775), files 0644
foreach ($allDirs as $d) {
    $rel = ltrim(str_replace($ROOT, '', $d), '/\\');
    $mode = is_writable_dir($rel, $WRITABLE) ? 0775 : 0755;
    @chmod($d, $mode);
}
foreach ($allFiles as $f) {
    @chmod($f, 0644);
}

// root khud
@chmod($ROOT, 0755);

echo "=========================================\n";
echo " PERMISSION FIX — DONE\n";
echo "=========================================\n\n";
echo "Folders processed : $dirs\n";
echo "Files processed   : $files\n";
echo "Writable folders  : " . implode(', ', $WRITABLE) . "  -> 0775\n";
echo "Baaki folders     : 0755\n";
echo "Saari files       : 0644\n\n";

if ($failed) {
    echo "Jinme permission nahi badal payi (" . count($failed) . "):\n";
    foreach (array_slice($failed, 0, 50) as $x) {
        echo "  - $x\n";
    }
    echo "\nYe files kisi aur user ki malik ho sakti hain.\n";
    echo "cPanel -> File Manager se manually delete karke dobara extract karo.\n\n";
} else {
    echo "Koi error nahi aaya. Ab apna zip DOBARA extract karo.\n\n";
}

echo "AB YE FILE DELETE KAR DENA:  fix-perms.php\n";
echo "(browser me khulne ke baad ise hata dena security ke liye)\n";
