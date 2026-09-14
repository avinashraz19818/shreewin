<?php
require_once __DIR__ . '/_common.php';
require_once __DIR__ . '/_betting_rebate.php';
api_require_post();
$body=api_input();
api_require_signature($body);
$user=api_user();
$codeType=(int)($body['codeType'] ?? -1);
if ($codeType !== -1 && $codeType !== 3) {
    api_send(['codeWashAmount'=>0.0,'dayRebate'=>0.0,'totalRebate'=>0.0,'washRate'=>betting_rebate_rate(),'washList'=>null]);
}
api_send(betting_rebate_summary((int)$user['id']));
