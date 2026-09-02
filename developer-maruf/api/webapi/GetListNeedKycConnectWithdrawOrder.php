<?php
require_once __DIR__.'/_common.php';api_require_post();$body=api_input();api_require_signature($body);api_user();api_send(['list'=>[],'pageNo'=>(int)($body['pageNo']??1),'totalPage'=>0,'totalCount'=>0]);
