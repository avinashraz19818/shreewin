<?php
require_once __DIR__.'/_common.php';api_require_post();$body=api_input();api_require_signature($body);api_user();$scheme=(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')?'https':'http';$host=preg_replace('/[^A-Za-z0-9.:-]/','',(string)($_SERVER['HTTP_HOST']??''));api_send($host!==''?[['url'=>$scheme.'://'.$host,'domain'=>$host,'state'=>1]]:[]);
