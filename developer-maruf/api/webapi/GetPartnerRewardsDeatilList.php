<?php
require_once __DIR__ . '/_common.php';require_once __DIR__ . '/_promotion_team.php';api_require_post();$body=api_input();api_require_signature($body);$user=api_user();$level=(int)($body['level']??$body['lv']??1);$page=(int)($body['pageNo']??1);$size=(int)($body['pageSize']??10);api_send(promotion_level_members((int)$user['id'],$level,$page,$size));
