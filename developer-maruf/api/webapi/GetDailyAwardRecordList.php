<?php
require_once __DIR__ . '/_common.php';
api_require_post();
$body = api_input();
api_require_signature($body);
$user = api_user();
$pageNo = max(1, (int)($body['pageNo'] ?? 1));
$pageSize = min(100, max(1, (int)($body['pageSize'] ?? 10)));
$offset = ($pageNo - 1) * $pageSize;
$userId = (int)$user['id'];
$count = 0;
$stmt = $conn->prepare('SELECT COUNT(*) FROM app_daily_award_claims WHERE user_id=?');
$stmt->bind_param('i', $userId); $stmt->execute(); $stmt->bind_result($count); $stmt->fetch(); $stmt->close();
$list = [];
$stmt = $conn->prepare('SELECT id,config_id,task_target,award_amount,created_at FROM app_daily_award_claims WHERE user_id=? ORDER BY id DESC LIMIT ? OFFSET ?');
$stmt->bind_param('iii', $userId, $pageSize, $offset); $stmt->execute(); $result = $stmt->get_result();
while ($row = $result->fetch_assoc()) $list[] = ['id'=>(int)$row['id'],'configId'=>(int)$row['config_id'],'taskTitle'=>'Daily betting bonus','taskTarget'=>(float)$row['task_target'],'taskAwardAmount'=>(float)$row['award_amount'],'status'=>3,'createDate'=>$row['created_at']];
$stmt->close();
api_send(['list'=>$list,'pageNo'=>$pageNo,'pageSize'=>$pageSize,'totalCount'=>(int)$count,'totalPage'=>(int)ceil($count/$pageSize)]);
