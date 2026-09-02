<?php

require_once __DIR__ . '/libs/psr/simple-cache/src/CacheException.php';
require_once __DIR__ . '/libs/psr/simple-cache/src/CacheInterface.php';
require_once __DIR__ . '/libs/psr/simple-cache/src/InvalidArgumentException.php';



spl_autoload_register(function ($class) {
    $prefixes = [
        'PhpOffice\\PhpSpreadsheet\\' => __DIR__ . '/libs/PhpSpreadsheet/',
        'Psr\\SimpleCache\\' => __DIR__ . '/libs/psr/simple-cache/src/',
        'Composer\\Pcre\\' => __DIR__ . '/libs/composer/pcre/src/'
    ];

    foreach ($prefixes as $prefix => $baseDir) {
        if (strpos($class, $prefix) === 0) {
            $relativeClass = substr($class, strlen($prefix));
            $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

            if (file_exists($file)) {
                require_once $file;  // Changed require to require_once to avoid redeclaration
                return;
            }else {
                echo "❌ Class file not found for: $class\n";
                echo "<br>";
            }
        }
    }
});

?>
