<?php
/** Included AFTER the existing connection. Never writes money or changes old records. */
try {
    require_once __DIR__.'/app_core_live_v4.php';
    if (app_table_exists('veegame_saas_settings')) {
        $state=app_setting('migration_state','preview');
        if (in_array($state,['frozen','active','paused'],true)) {
            header('Content-Type: application/json');http_response_code(409);
            echo json_encode(['code'=>409,'msg'=>'WinGo has moved to the SaaS screen; legacy betting is closed','data'=>null]);exit;
        }
    }
} catch (Throwable $e) {
    header('Content-Type: application/json');http_response_code(503);
    echo json_encode(['code'=>503,'msg'=>'Betting migration status unavailable','data'=>null]);exit;
}
