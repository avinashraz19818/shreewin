<?php
include("conn.php");

$query = mysqli_query($conn,"
SELECT s.id,s.mobile,s.status,k.motta
FROM shonu_subjects s
LEFT JOIN shonu_kaichila k ON s.id=k.balakedara
ORDER BY s.id DESC
");

$data=[];

while($r=mysqli_fetch_assoc($query)){

$statusBtn = $r['status']==1
? "<button class='btn btn-ban' onclick='toggleBan({$r['id']},0)'>Ban</button>"
: "<button class='btn btn-unban' onclick='toggleBan({$r['id']},1)'>Unban</button>";

$data[]=[
$r['mobile'],
$r['motta'],
$r['status']==1?'Active':'Banned',
$r['id'],
"$statusBtn 
<button class='btn btn-edit' onclick='editWallet({$r['id']})'>Wallet</button>
<button class='btn' onclick='viewUser({$r['id']})'>Details</button>"
];
}

echo json_encode([
"data"=>$data
]);