<?php
include "function2.php";


function getBonusQuery($type, $shonuid, $pageSize, $samatolana, $date){
	$tableNames = [
		3 => "hodike_balakedara",
		10 => "recharge_gift_table",
		13 => "bonus_recharge_table",
		14 => "first_full_gift_table",
		20 => "invite_bonus_table",
		25 => "card_binding_gift_table",
		107 => "weekly_awards_table",
		118 => "daily_awards_table",
		117 => "new_members_bonus_table",
		115 => "return_awards_table",
	];
	$query = "SELECT kani, price, remark , shonu
			FROM " . $tableNames[$type] . " WHERE userkani = $shonuid
			ORDER BY shonu DESC LIMIT $pageSize OFFSET $samatolana";
	$querydate = "SELECT kani, price, shonu, remark
	FROM " . $tableNames[$type] . " WHERE userkani = $shonuid AND date(shonu) = date('" . $date . "')
	ORDER BY shonu DESC LIMIT $pageSize OFFSET $samatolana";
	return $date == '' ? $query : $querydate;
}

function getBonusQueryCnt($type, $shonuid, $date){
	$tableNames = [
		3 => "hodike_balakedara",
		10 => "recharge_gift_table",
		13 => "bonus_recharge_table",
		14 => "first_full_gift_table",
		20 => "invite_bonus_table",
		25 => "card_binding_gift_table",
		107 => "weekly_awards_table",
		118 => "daily_awards_table",
		117 => "new_members_bonus_table",
		115 => "return_awards_table",
	];
	$query = "SELECT kani FROM " . $tableNames[$type] . " WHERE userkani = $shonuid";
	$querydate = "SELECT kani FROM " . $tableNames[$type] . " WHERE userkani = $shonuid AND date(shonu) = date('" . $date . "')";
	return $date == '' ? $query : $querydate;
}


function getTransactions($conn, $shonuid, $type, $date,  $pageNo) {
    $pageSize=10;
	$samatolana = ($pageNo - 1) * $pageSize;
	if ($date == '') {
		if ($type == -1) {
			$samasye = "SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
								  FROM bajikattuttate WHERE byabaharkarta = $shonuid 
                                  UNION ALL
								  SELECT kramasankhye as parichaya, bonus as ketebida, 'sb' as phalaphala, bonus as sesabida, dinankavannuracisi as tiarikala 
								  FROM shonu_kaichila WHERE balakedara = $shonuid
                                  UNION ALL
								  SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
								  FROM bajikattuttate_drei WHERE byabaharkarta = $shonuid
                                  UNION ALL
								  SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
								  FROM bajikattuttate_trx WHERE byabaharkarta = $shonuid
                                  UNION ALL
								  SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
								  FROM bajikattuttate_trx3 WHERE byabaharkarta = $shonuid
                                  UNION ALL
								  SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
								  FROM bajikattuttate_trx5 WHERE byabaharkarta = $shonuid
                                  UNION ALL
								  SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
								  FROM bajikattuttate_trx10 WHERE byabaharkarta = $shonuid
                                  UNION ALL
								  SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
								  FROM bajikattuttate_funf WHERE byabaharkarta = $shonuid
                                  UNION ALL
								  SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
								  FROM bajikattuttate_zehn WHERE byabaharkarta = $shonuid
                                  UNION ALL
								  SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
								  FROM bajikattuttate_aidudi WHERE byabaharkarta = $shonuid
                                  UNION ALL
								  SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
								  FROM bajikattuttate_aidudi_drei WHERE byabaharkarta = $shonuid
                                  UNION ALL
								  SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
								  FROM bajikattuttate_aidudi_funf WHERE byabaharkarta =$shonuid 
                                  UNION ALL
								  SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
								  FROM bajikattuttate_aidudi_zehn WHERE byabaharkarta =$shonuid 
                                  UNION ALL
								  SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
								  FROM bajikattuttate_kemuru WHERE byabaharkarta = $shonuid
                                  UNION ALL
								  SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
								  FROM bajikattuttate_kemuru_drei WHERE byabaharkarta = $shonuid
                                  UNION ALL
								  SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
								  FROM bajikattuttate_kemuru_funf WHERE byabaharkarta = $shonuid
                                  UNION ALL
								  SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
								  FROM bajikattuttate_kemuru_zehn WHERE byabaharkarta = $shonuid

								  UNION ALL
								  SELECT macau as parichaya, salary as ketebida, 'ds' as phalaphala, salary as sesabida, createdate as tiarikala 
								  FROM dailysalary WHERE userid = $shonuid
								  UNION ALL
								  SELECT shonu as parichaya, motta as ketebida, 'rc' as phalaphala, motta as sesabida, dinankavannuracisi as tiarikala 
								  FROM thevani WHERE balakedara = $shonuid AND sthiti = 1
								  UNION ALL
								  SELECT id as parichaya, sturgis as ketebida, 'frc' as phalaphala, sturgis as sesabida, time as tiarikala 
								  FROM egrahcer_sonub WHERE dr = $shonuid AND status = 1
								  UNION ALL
								  SELECT id as parichaya, prize as ketebida, 'rb' as phalaphala, prize as sesabida, time as tiarikala 
								  FROM spinrec WHERE user_id = $shonuid
								  UNION ALL
								  SELECT dearlord as parichaya, todayblessings as ketebida, 'atb' as phalaphala, todayblessings as sesabida, amen as tiarikala 
								  FROM cihne WHERE identity = $shonuid
								  UNION ALL
								  SELECT shonu as parichaya, motta as ketebida, 'wd' as phalaphala, remarks as sesabida, dinankavannuracisi as tiarikala 
								  FROM hintegedukolli WHERE balakedara = $shonuid 
								  UNION ALL
								  SELECT id as parichaya, motta as ketebida, 'orb' as phalaphala, motta as sesabida, created_at as tiarikala 
								  FROM rebetrec WHERE user_id = $shonuid
								  UNION ALL
								  SELECT id as parichaya, motta as ketebida, 'lvlup' as phalaphala, type as sesabida, created_at as tiarikala 
								  FROM viprec WHERE user_id = $shonuid
								  UNION ALL
								  SELECT id as parichaya, rebateAmount_Last as ketebida, 'cmd' as phalaphala, rebateAmount_Last as sesabida, created_timestamp as tiarikala 
								  FROM commission WHERE user_id = $shonuid
								  UNION ALL
								  SELECT id as parichaya, motta as ketebida, 'reftask' as phalaphala, motta as sesabida, time as tiarikala 
								  FROM noitativni_sonub WHERE arthur = $shonuid AND status = 1 
								  UNION ALL
								  SELECT kani as parichaya, price as ketebida, 're' as phalaphala, remark as sesabida, shonu as tiarikala 
								  FROM hodike_balakedara WHERE userkani = $shonuid 
								  UNION ALL
								  SELECT id as parichaya, price as ketebida, 'ibt' as phalaphala, remark as sesabida, shonu as tiarikala 
								  FROM invite_bonus_table WHERE userkani = $shonuid 
								  
								  UNION ALL
								  SELECT id as parichaya, reward_amount as ketebida, 'ptreward' as phalaphala, invitation_code as sesabida, last_updated as tiarikala 
								  FROM partner_rewards WHERE user_id = $shonuid 
								  
								  UNION ALL
								  SELECT id as parichaya, amount as ketebida, 'agcmsn' as phalaphala, level as sesabida, date as tiarikala 
								  FROM to_be_add_comission WHERE user_id = $shonuid 
								  
								  
								  
								  UNION ALL
								  SELECT id as parichaya, price as ketebida, 'brdt' as phalaphala, remark as sesabida, shonu as tiarikala 
								  FROM balance_detuct_table WHERE userkani = $shonuid
								  
								  
								  
								  UNION ALL
								  SELECT id as parichaya, price as ketebida, 'brt' as phalaphala, remark as sesabida, shonu as tiarikala 
								  FROM bonus_recharge_table WHERE userkani = $shonuid 
								  
								  UNION ALL
								  SELECT id as parichaya, price as ketebida, 'ffgt' as phalaphala, remark as sesabida, shonu as tiarikala 
								  FROM first_full_gift_table WHERE userkani = $shonuid 
								  
								  UNION ALL
								  SELECT id as parichaya, price as ketebida, 'cbgt' as phalaphala, remark as sesabida, shonu as tiarikala 
								  FROM card_binding_gift_table WHERE userkani = $shonuid 
								  
								  UNION ALL
								  SELECT id as parichaya, price as ketebida, 'wat' as phalaphala, remark as sesabida, shonu as tiarikala 
								  FROM weekly_awards_table WHERE userkani = $shonuid 
								  
								  UNION ALL
								  SELECT id as parichaya, price as ketebida, 'dat' as phalaphala, remark as sesabida, shonu as tiarikala 
								  FROM daily_awards_table WHERE userkani = $shonuid 
								  
								  UNION ALL
								  SELECT id as parichaya, price as ketebida, 'nmbt' as phalaphala, remark as sesabida, shonu as tiarikala 
								  FROM new_members_bonus_table WHERE userkani = $shonuid 
								  
								  UNION ALL
								  SELECT id as parichaya, price as ketebida, 'rat' as phalaphala, remark as sesabida, shonu as tiarikala 
								  FROM return_awards_table WHERE userkani = $shonuid 
    					
								  UNION ALL
	                              SELECT id as parichaya, price as ketebida, 'apigbmo' as phalaphala, remark as sesabida, shonu as tiarikala 
	                              FROM aks_amount_in_out_table WHERE processed ='0' and userkani = $shonuid 
	  
	                              UNION ALL
	                              SELECT id as parichaya, price as ketebida, 'apigbmi' as phalaphala, remark as sesabida, shonu as tiarikala 
	                              FROM aks_amount_in_out_table WHERE processed ='1' and  userkani = $shonuid 
	                              
	                              
								  UNION ALL
								  SELECT id as parichaya, price as ketebida, 'acht' as phalaphala, remark as sesabida, shonu as tiarikala 
								  FROM aks_comission_history_table WHERE userkani = $shonuid
	                              
								  
								  ORDER BY tiarikala DESC LIMIT 
$pageSize OFFSET $samatolana";


// UNION ALL   
// 	                              SELECT id as parichaya, amount as ketebida, 'abt' as phalaphala, type as sesabida, date as tiarikala
// 	                              FROM agent_bonus_table WHERE user_id = $shonuid

// YE wala ADD KRNA H BAAD ME

/*
 
	  UNION ALL
	  SELECT kani as parichaya, price as ketebida, 'apigbmo' as phalaphala, remark as sesabida, shonu as tiarikala 
	  FROM aks_amount_in_out_table WHERE transaction_type ='out' and userkani = $shonuid 
	  
	  UNION ALL
	  SELECT kani as parichaya, price as ketebida, 'apigbmi' as phalaphala, remark as sesabida, shonu as tiarikala 
	  FROM aks_amount_in_out_table WHERE transaction_type ='in' and  userkani = $shonuid 
	  
	  */

								$samasyephalitansa = $conn->query($samasye);
								
								$samasye_ondu = "SELECT parichaya
								  FROM bajikattuttate WHERE byabaharkarta = $shonuid
                                  UNION ALL
								  SELECT kramasankhye as parichaya
								  FROM shonu_kaichila WHERE balakedara = $shonuid
								  UNION ALL
								  SELECT parichaya
								  FROM bajikattuttate_drei WHERE byabaharkarta = $shonuid
                                  UNION ALL
								  SELECT parichaya
								  FROM bajikattuttate_trx WHERE byabaharkarta = $shonuid
                                  UNION ALL
								  SELECT parichaya
								  FROM bajikattuttate_trx3 WHERE byabaharkarta = $shonuid
                                  UNION ALL
								  SELECT parichaya
								  FROM bajikattuttate_trx5 WHERE byabaharkarta = $shonuid
                                  UNION ALL
								  SELECT parichaya
								  FROM bajikattuttate_trx10 WHERE byabaharkarta = $shonuid
								  UNION ALL
								  SELECT parichaya
								  FROM bajikattuttate_funf WHERE byabaharkarta = $shonuid
								  UNION ALL
								  SELECT parichaya
								  FROM bajikattuttate_zehn WHERE byabaharkarta = $shonuid
								  UNION ALL
								  SELECT parichaya
								  FROM bajikattuttate_aidudi WHERE byabaharkarta = $shonuid
								  UNION ALL
								  SELECT parichaya
								  FROM bajikattuttate_aidudi_drei WHERE byabaharkarta = $shonuid
								  UNION ALL
								  SELECT parichaya
								  FROM bajikattuttate_aidudi_funf WHERE byabaharkarta = $shonuid
								  UNION ALL
								  SELECT parichaya
								  FROM bajikattuttate_aidudi_zehn WHERE byabaharkarta = $shonuid
								  UNION ALL
								  SELECT parichaya
								  FROM bajikattuttate_kemuru WHERE byabaharkarta = $shonuid
								  UNION ALL
								  SELECT parichaya
								  FROM bajikattuttate_kemuru_drei WHERE byabaharkarta = $shonuid
								  UNION ALL
								  SELECT parichaya
								  FROM bajikattuttate_kemuru_funf WHERE byabaharkarta = $shonuid
								  UNION ALL
								  SELECT parichaya
								  FROM bajikattuttate_kemuru_zehn WHERE byabaharkarta = $shonuid
								  UNION ALL
								  SELECT macau as parichaya
								  FROM dailysalary WHERE userid = $shonuid
								  UNION ALL
								  SELECT shonu as parichaya
								  FROM thevani WHERE balakedara = $shonuid AND sthiti = 1
								  UNION ALL
								  SELECT shonu as parichaya
								  FROM hintegedukolli WHERE balakedara = $shonuid
								  UNION ALL
								  SELECT id as parichaya
								  FROM noitativni_sonub WHERE arthur = $shonuid AND status = 1
								  UNION ALL
								  SELECT id as parichaya
								  FROM rebetrec WHERE user_id = $shonuid
								  UNION ALL
								  SELECT dearlord as parichaya
								  FROM cihne WHERE identity = $shonuid
								  UNION ALL
								  SELECT id as parichaya
								  FROM spinrec WHERE user_id = $shonuid
								  UNION ALL
								  SELECT id as parichaya
								  FROM viprec WHERE user_id = $shonuid
								  UNION ALL
								  SELECT id as parichaya
								  FROM egrahcer_sonub WHERE dr = $shonuid
								  UNION ALL
								  SELECT id as parichaya
								  FROM rebetrec WHERE user_id = $shonuid
								  UNION ALL
								  SELECT kani as parichaya
								  FROM hodike_balakedara WHERE userkani = $shonuid" 
								  
								  ;
								$samasyephalitansa_ondu = $conn->query($samasye_ondu);
								$samasyephalitansa_sankhye = mysqli_num_rows($samasyephalitansa_ondu);
		}
		else if ($type == 0) {
			$samasye = "SELECT parichhaya, ketebida, phalaphala, sesabida, tiarikala  
FROM bajikattutatte WHERE byabaharkarta = $shonuid
UNION ALL
SELECT parichhaya, ketebida, phalaphala, sesabida, tiarikala  
FROM bajikattutatte_drei WHERE byabaharkarta = $shonuid
UNION ALL
SELECT parichhaya, ketebida, phalaphala, sesabida, tiarikala  
FROM bajikattutatte_funf WHERE byabaharkarta = $shonuid
UNION ALL
SELECT parichhaya, ketebida, phalaphala, sesabida, tiarikala  
FROM bajikattutatte_zehn WHERE byabaharkarta = $shonuid
UNION ALL
SELECT parichhaya, ketebida, phalaphala, sesabida, tiarikala  
FROM bajikattutatte_aidudi WHERE byabaharkarta = $shonuid
UNION ALL
SELECT parichhaya, ketebida, phalaphala, sesabida, tiarikala  
FROM bajikattutatte_aidudi_drei WHERE byabaharkarta = $shonuid
UNION ALL
SELECT parichhaya, ketebida, phalaphala, sesabida, tiarikala  
FROM bajikattutatte_aidudi_funf WHERE byabaharkarta = $shonuid
UNION ALL
SELECT parichhaya, ketebida, phalaphala, sesabida, tiarikala  
FROM bajikattutatte_aidudi_zehn WHERE byabaharkarta = $shonuid
UNION ALL
SELECT parichhaya, ketebida, phalaphala, sesabida, tiarikala  
FROM bajikattutatte_kemuru WHERE byabaharkarta = $shonuid
UNION ALL
SELECT parichhaya, ketebida, phalaphala, sesabida, tiarikala  
FROM bajikattutatte_kemuru_drei WHERE byabaharkarta = $shonuid
UNION ALL
SELECT parichhaya, ketebida, phalaphala, sesabida, tiarikala  
FROM bajikattutatte_kemuru_funf WHERE byabaharkarta = $shonuid
UNION ALL
SELECT parichhaya, ketebida, phalaphala, sesabida, tiarikala  
FROM bajikattutatte_kemuru_zehn WHERE byabaharkarta = $shonuid
ORDER BY tiarikala DESC LIMIT $pageSize OFFSET $samatolana
";
								$samasyephalitansa = $conn->query($samasye);
								
								$samasye_ondu = "SELECT parichaya
								  FROM bajikattuttate WHERE byabaharkarta = $shonuid
								  UNION ALL
								  SELECT parichaya
								  FROM bajikattuttate_drei WHERE byabaharkarta = $shonuid
								  UNION ALL
								  SELECT parichaya
								  FROM bajikattuttate_funf WHERE byabaharkarta = $shonuid
								  UNION ALL
								  SELECT parichaya
								  FROM bajikattuttate_zehn WHERE byabaharkarta = $shonuid
								  UNION ALL
								  SELECT parichaya
								  FROM bajikattuttate_aidudi WHERE byabaharkarta = $shonuid
								  UNION ALL
								  SELECT parichaya
								  FROM bajikattuttate_aidudi_drei WHERE byabaharkarta = $shonuid
								  UNION ALL
								  SELECT parichaya
								  FROM bajikattuttate_aidudi_funf WHERE byabaharkarta = $shonuid
								  UNION ALL
								  SELECT parichaya
								  FROM bajikattuttate_aidudi_zehn WHERE byabaharkarta = $shonuid
								  UNION ALL
								  SELECT parichaya
								  FROM bajikattuttate_kemuru WHERE byabaharkarta = $shonuid
								  UNION ALL
								  SELECT parichaya
								  FROM bajikattuttate_kemuru_drei WHERE byabaharkarta = $shonuid
								  UNION ALL
								  SELECT parichaya
								  FROM bajikattuttate_kemuru_funf WHERE byabaharkarta = $shonuid
								  UNION ALL
								  SELECT parichaya
								  FROM bajikattuttate_kemuru_zehn WHERE byabaharkarta = $shonuid";
								$samasyephalitansa_ondu = $conn->query($samasye_ondu);
								$samasyephalitansa_sankhye = mysqli_num_rows($samasyephalitansa_ondu);
			}
			else if ($type == 1) {
			$samasye = "\x53\x45\x4c\x45\x43\x54\x20\x6d\x61\x63\x61\x75\x2c\x20\x73\x61\x6c\x61\x72\x79\x2c\x20\x63\x72\x65\x61\x74\x65\x64\x61\x74\x65\xa\x9\x9\x9\x9\x9\x9\x9\x9\x20\x20\x46\x52\x4f\x4d\x20\x64\x61\x69\x6c\x79\x73\x61\x6c\x61\x72\x79\x20\x57\x48\x45\x52\x45\x20\x75\x73\x65\x72\x69\x64\x20\x3d\x20$shonuid								  
								  \x4f\x52\x44\x45\x52\x20\x42\x59\x20\x6d\x61\x63\x61\x75\x20\x44\x45\x53\x43\x20\x4c\x49\x4d\x49\x54\x20$pageSize\x20\x4f\x46\x46\x53\x45\x54\x20$samatolana";
			$samasyephalitansa = $conn->query($samasye);

			$samasye_ondu = "\x53\x45\x4c\x45\x43\x54\x20\x6d\x61\x63\x61\x75\xa\x9\x9\x9\x9\x9\x9\x9\x9\x20\x20\x46\x52\x4f\x4d\x20\x64\x61\x69\x6c\x79\x73\x61\x6c\x61\x72\x79\x20\x57\x48\x45\x52\x45\x20\x75\x73\x65\x72\x69\x64\x20\x3d\x20$shonuid";
			$samasyephalitansa_ondu = $conn->query($samasye_ondu);
			$samasyephalitansa_sankhye = mysqli_num_rows($samasyephalitansa_ondu);
		} else if ($type == 4) {
			$samasye = "\x53\x45\x4c\x45\x43\x54\x20\x73\x68\x6f\x6e\x75\x2c\x20\x6d\x6f\x74\x74\x61\x2c\x20\x64\x69\x6e\x61\x6e\x6b\x61\x76\x61\x6e\x6e\x75\x72\x61\x63\x69\x73\x69\xd\xa\x9\x9\x9\x9\x9\x9\x9\x9\x20\x20\x46\x52\x4f\x4d\x20\x74\x68\x65\x76\x61\x6e\x69\x20\x57\x48\x45\x52\x45\x20\x62\x61\x6c\x61\x6b\x65\x64\x61\x72\x61\x20\x3d\x20$shonuid\x20\x41\x4e\x44\x20\x73\x74\x68\x69\x74\x69\x20\x3d\x20\x31
								  \x4f\x52\x44\x45\x52\x20\x42\x59\x20\x64\x69\x6e\x61\x6e\x6b\x61\x76\x61\x6e\x6e\x75\x72\x61\x63\x69\x73\x69\x20\x44\x45\x53\x43\x20\x4c\x49\x4d\x49\x54\x20$pageSize\x20\x4f\x46\x46\x53\x45\x54\x20$samatolana";
			$samasyephalitansa = $conn->query($samasye);

			$samasye_ondu = "\x53\x45\x4c\x45\x43\x54\x20\x73\x68\x6f\x6e\x75\x2c\x20\x6d\x6f\x74\x74\x61\x2c\x20\x64\x69\x6e\x61\x6e\x6b\x61\x76\x61\x6e\x6e\x75\x72\x61\x63\x69\x73\x69\xd\xa\x9\x9\x9\x9\x9\x9\x9\x9\x20\x20\x46\x52\x4f\x4d\x20\x74\x68\x65\x76\x61\x6e\x69\x20\x57\x48\x45\x52\x45\x20\x62\x61\x6c\x61\x6b\x65\x64\x61\x72\x61\x20\x3d\x20$shonuid\x20\x41\x4e\x44\x20\x73\x74\x68\x69\x74\x69\x20\x3d\x20\x31";
			$samasyephalitansa_ondu = $conn->query($samasye_ondu);
			$samasyephalitansa_sankhye = mysqli_num_rows($samasyephalitansa_ondu);
		} else if ($type == 119) {
			$samasye = "SELECT id, prize, time
                                      FROM spinrec
                                      WHERE user_id = $shonuid
                                      ORDER BY time DESC LIMIT $pageSize OFFSET $samatolana";
			$samasyephalitansa = $conn->query($samasye);

			$samasye_ondu = "SELECT id, prize, time
                                         FROM spinrec
                                        WHERE user_id = $shonuid";
			$samasyephalitansa_ondu = $conn->query($samasye_ondu);
			$samasyephalitansa_sankhye = mysqli_num_rows($samasyephalitansa_ondu);

		} else if ($type == 12) {
			$samasye = "SELECT kramasankhye, bonus, dinankavannuracisi
									   FROM shonu_kaichila
									   WHERE balakedara = $shonuid
									   ORDER BY dinankavannuracisi DESC LIMIT $pageSize OFFSET $samatolana";
			$samasyephalitansa = $conn->query($samasye);

			$samasye_ondu = "SELECT kramasankhye, bonus, dinankavannuracisi
										  FROM shonu_kaichila
										 WHERE balakedara = $shonuid";
			$samasyephalitansa_ondu = $conn->query($samasye_ondu);
			$samasyephalitansa_sankhye = mysqli_num_rows($samasyephalitansa_ondu);

		} else if ($type == 5) {
			$samasye = "SELECT shonu, motta, dinankavannuracisi, remarks
                                            FROM hintegedukolli 
                                            WHERE balakedara = $shonuid AND madari <> 6
                                            ORDER BY dinankavannuracisi DESC 
                                          LIMIT $pageSize OFFSET $samatolana";
			$samasyephalitansa = $conn->query($samasye);

			$samasye_ondu = "SELECT shonu, motta, dinankavannuracisi, remarks
                                                 FROM hintegedukolli 
                                                 WHERE balakedara = $shonuid  AND madari <> 6";
			$samasyephalitansa_ondu = $conn->query($samasye_ondu);
			$samasyephalitansa_sankhye = mysqli_num_rows($samasyephalitansa_ondu);
		} else if ($type == 6) {
			$samasye = "SELECT shonu, motta, dinankavannuracisi, remarks
                                            FROM hintegedukolli 
                                            WHERE balakedara = $shonuid AND madari = 6
                                            ORDER BY dinankavannuracisi DESC 
                                          LIMIT $pageSize OFFSET $samatolana";
			$samasyephalitansa = $conn->query($samasye);

			$samasye_ondu = "SELECT shonu, motta, dinankavannuracisi, remarks
                                                 FROM hintegedukolli 
                                                 WHERE balakedara = $shonuid AND madari = 6";
			$samasyephalitansa_ondu = $conn->query($samasye_ondu);
			$samasyephalitansa_sankhye = mysqli_num_rows($samasyephalitansa_ondu);
		} else if ($type == 2) {
			$samasye = "SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
								  FROM bajikattuttate WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner'
								  UNION ALL
								  SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
								  FROM bajikattuttate_drei WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner'
								  UNION ALL
								  SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
								  FROM bajikattuttate_funf WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner'
								  UNION ALL
								  SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
								  FROM bajikattuttate_zehn WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner'
								  UNION ALL
								  SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
								  FROM bajikattuttate_aidudi WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner'
								  UNION ALL
								  SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
								  FROM bajikattuttate_aidudi_drei WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner'
								  UNION ALL
								  SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
								  FROM bajikattuttate_aidudi_funf WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner'
								  UNION ALL
								  SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
								  FROM bajikattuttate_aidudi_zehn WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner'
								  UNION ALL
								  SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
								  FROM bajikattuttate_kemuru WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner'
								  UNION ALL
								  SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
								  FROM bajikattuttate_kemuru_drei WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner'
								  UNION ALL
								  SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
								  FROM bajikattuttate_kemuru_funf WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner'
								  UNION ALL
								  SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
								  FROM bajikattuttate_kemuru_zehn WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner'
								  ORDER BY tiarikala DESC LIMIT $pageSize OFFSET $samatolana";
			$samasyephalitansa = $conn->query($samasye);

			$samasye_ondu = "SELECT parichaya
								  FROM bajikattuttate WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner'
								  UNION ALL
								  SELECT parichaya
								  FROM bajikattuttate_drei WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner'
								  UNION ALL
								  SELECT parichaya
								  FROM bajikattuttate_funf WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner'
								  UNION ALL
								  SELECT parichaya
								  FROM bajikattuttate_zehn WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner'
								  UNION ALL
								  SELECT parichaya
								  FROM bajikattuttate_aidudi WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner'
								  UNION ALL
								  SELECT parichaya
								  FROM bajikattuttate_aidudi_drei WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner'
								  UNION ALL
								  SELECT parichaya
								  FROM bajikattuttate_aidudi_funf WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner'
								  UNION ALL
								  SELECT parichaya
								  FROM bajikattuttate_aidudi_zehn WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner'
								  UNION ALL
								  SELECT parichaya
								  FROM bajikattuttate_kemuru WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner'
								  UNION ALL
								  SELECT parichaya
								  FROM bajikattuttate_kemuru_drei WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner'
								  UNION ALL
								  SELECT parichaya
								  FROM bajikattuttate_kemuru_funf WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner'
								  UNION ALL
								  SELECT parichaya
								  FROM bajikattuttate_kemuru_zehn WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner'";
			$samasyephalitansa_ondu = $conn->query($samasye_ondu);
			$samasyephalitansa_sankhye = mysqli_num_rows($samasyephalitansa_ondu);
		} else if (in_array($type, [3, 8, 10, 13, 14, 20, 21, 22, 25, 107, 124, 118, 117, 115])) {
			$samasye = getBonusQuery($type, $shonuid, $pageSize, $samatolana, $date);
			$samasyephalitansa = $conn->query($samasye);

			$samasye_ondu = getBonusQueryCnt($type, $shonuid, $date);
			$samasyephalitansa_ondu = $conn->query($samasye_ondu);
			$samasyephalitansa_sankhye = mysqli_num_rows($samasyephalitansa_ondu);
		} else if ($type == 14) {
			$samasye = "\x53\x45\x4c\x45\x43\x54\x20\x69\x64\x2c\x20\x73\x74\x75\x72\x67\x69\x73\x2c\x20\x74\x69\x6d\x65\xd\xa\x9\x9\x9\x9\x9\x9\x9\x9\x20\x20\x46\x52\x4f\x4d\x20\x65\x67\x72\x61\x68\x63\x65\x72\x5f\x73\x6f\x6e\x75\x62\x20\x57\x48\x45\x52\x45\x20\x64\x72\x20\x3d\x20$shonuid
								 \x20\x4f\x52\x44\x45\x52\x20\x42\x59\x20\x74\x69\x6d\x65\x20\x44\x45\x53\x43\x20\x4c\x49\x4d\x49\x54\x20$pageSize\x20\x4f\x46\x46\x53\x45\x54\x20$samatolana";
			$samasyephalitansa = $conn->query($samasye);

			$samasye_ondu = "\x53\x45\x4c\x45\x43\x54\x20\x69\x64\xd\xa\x9\x9\x9\x9\x9\x9\x9\x9\x20\x20\x46\x52\x4f\x4d\x20\x65\x67\x72\x61\x68\x63\x65\x72\x5f\x73\x6f\x6e\x75\x62\x20\x57\x48\x45\x52\x45\x20\x64\x72\x20\x3d\x20$shonuid";
			$samasyephalitansa_ondu = $conn->query($samasye_ondu);
			$samasyephalitansa_sankhye = mysqli_num_rows($samasyephalitansa_ondu);
		}
	} else {
		if ($type == -1) {
		$samasye = "SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
								  FROM bajikattuttate WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('" . $date . "')
                                  UNION ALL
								  SELECT kramasankhye as parichaya, bonus as ketebida, 'sb' as phalaphala, bonus as sesabida, dinankavannuracisi as tiarikala 
								  FROM shonu_kaichila WHERE balakedara = $shonuid AND date(dinankavannuracisi) = date('" . $date . "')
                                  UNION ALL
								  SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
								  FROM bajikattuttate_drei WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('" . $date . "')
                                  UNION ALL
								  SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
								  FROM bajikattuttate_trx WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('" . $date . "')
                                  UNION ALL
								  SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
								  FROM bajikattuttate_trx3 WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('" . $date . "')
                                  UNION ALL
								  SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
								  FROM bajikattuttate_trx5 WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('" . $date . "')
                                  UNION ALL
								  SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
								  FROM bajikattuttate_trx10 WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('" . $date . "')
                                  UNION ALL
								  SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
								  FROM bajikattuttate_funf WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('" . $date . "')
                                  UNION ALL
								  SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
								  FROM bajikattuttate_zehn WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('" . $date . "')
                                  UNION ALL
								  SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
								  FROM bajikattuttate_aidudi WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('" . $date . "')
                                  UNION ALL
								  SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
								  FROM bajikattuttate_aidudi_drei WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('" . $date . "')
                                  UNION ALL
								  SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
								  FROM bajikattuttate_aidudi_funf WHERE byabaharkarta =$shonuid  AND date(tiarikala) = date('" . $date . "')
                                  UNION ALL
								  SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
								  FROM bajikattuttate_aidudi_zehn WHERE byabaharkarta =$shonuid AND date(tiarikala) = date('" . $date . "')
                                  UNION ALL
								  SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
								  FROM bajikattuttate_kemuru WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('" . $date . "')
                                  UNION ALL
								  SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
								  FROM bajikattuttate_kemuru_drei WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('" . $date . "')
                                  UNION ALL
								  SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
								  FROM bajikattuttate_kemuru_funf WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('" . $date . "')
                                  UNION ALL
								  SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
								  FROM bajikattuttate_kemuru_zehn WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('" . $date . "')

								  UNION ALL
								  SELECT macau as parichaya, salary as ketebida, 'ds' as phalaphala, salary as sesabida, createdate as tiarikala 
								  FROM dailysalary WHERE userid = $shonuid AND date(createdate) = date('" . $date . "') 
								  UNION ALL
								  SELECT shonu as parichaya, motta as ketebida, 'rc' as phalaphala, motta as sesabida, dinankavannuracisi as tiarikala
								  FROM thevani WHERE balakedara = $shonuid AND sthiti = 1 AND date(dinankavannuracisi) = date('" . $date . "') 
								  UNION ALL
								  SELECT id as parichaya, sturgis as ketebida, 'frc' as phalaphala, sturgis as sesabida, time as tiarikala 
								  FROM egrahcer_sonub WHERE dr = $shonuid AND  date(time) = date('" . $date . "')
								  UNION ALL
								  SELECT id as parichaya, prize as ketebida, 'rb' as phalaphala, prize as sesabida, time as tiarikala 
								  FROM spinrec WHERE user_id = $shonuid AND date(time) = date('" . $date . "') 
								  UNION ALL
								  SELECT dearlord as parichaya, todayblessings as ketebida, 'atb' as phalaphala, todayblessings as sesabida, amen as tiarikala 
								  FROM cihne WHERE identity = $shonuid AND date(amen) = date('" . $date . "')
								  UNION ALL
								  
								  SELECT shonu as parichaya, motta as ketebida, 'wd' as phalaphala, remarks as sesabida, dinankavannuracisi as tiarikala 
								  FROM hintegedukolli WHERE balakedara = $shonuid AND date(dinankavannuracisi) = date('" . $date . "') AND madari <> 6 
								  UNION ALL
								  SELECT shonu as parichaya, motta as ketebida, 'cwd' as phalaphala, remarks as sesabida, dinankavannuracisi as tiarikala 
								  FROM hintegedukolli WHERE balakedara = $shonuid AND date(dinankavannuracisi) = date('" . $date . "') AND madari = 6
								  
								  UNION ALL
								  SELECT id as parichaya, motta as ketebida, 'orb' as phalaphala, motta as sesabida, created_at as tiarikala 
								  FROM rebetrec WHERE user_id = $shonuid AND date(created_at) = date('" . $date . "')
								  UNION ALL
								  SELECT id as parichaya, motta as ketebida, 'lvlup' as phalaphala , type as sesabida, created_at as tiarikala 
								  FROM viprec WHERE user_id = $shonuid AND date(created_at) = date('" . $date . "')
								  UNION ALL
								  SELECT id as parichaya, rebateAmount_Last as ketebida, 'cmd' as phalaphala, rebateAmount_Last as sesabida, created_timestamp as tiarikala 
								  FROM commission WHERE user_id = $shonuid AND date(created_timestamp) = date('" . $date . "')
								  UNION ALL
								  SELECT id as parichaya, motta as ketebida, 'reftask' as phalaphala, motta as sesabida, time as tiarikala 
								  FROM noitativni_sonub WHERE arthur = $shonuid AND date(time) = date('" . $date . "') 
								  UNION ALL
								  SELECT kani as parichaya, price as ketebida, 're' as phalaphala, remark as sesabida, shonu as tiarikala 
								  FROM hodike_balakedara WHERE userkani = $shonuid  AND date(shonu) = date('" . $date . "') 
								 
								  UNION ALL
								  SELECT id as parichaya, price as ketebida, 'rgt' as phalaphala, remark as sesabida, shonu as tiarikala 
								  FROM recharge_gift_table WHERE userkani = $shonuid  AND date(shonu) = date('" . $date . "') 
								  UNION ALL
								  SELECT id as parichaya, price as ketebida, 'brt' as phalaphala, remark as sesabida, shonu as tiarikala 
								  FROM bonus_recharge_table WHERE userkani = $shonuid  AND date(shonu) = date('" . $date . "') 
								  UNION ALL
								  SELECT id as parichaya, price as ketebida, 'ffgt' as phalaphala, remark as sesabida, shonu as tiarikala 
								  FROM first_full_gift_table WHERE userkani = $shonuid  AND date(shonu) = date('" . $date . "') 
								  UNION ALL
								  SELECT id as parichaya, price as ketebida, 'ibt' as phalaphala, remark as sesabida, shonu as tiarikala 
								  FROM invite_bonus_table WHERE userkani = $shonuid  AND date(shonu) = date('" . $date . "') 
								  UNION ALL
								  SELECT id as parichaya, price as ketebida, 're' as phalaphala, remark as sesabida, shonu as tiarikala 
								  FROM card_binding_gift_table WHERE userkani = $shonuid  AND date(shonu) = date('" . $date . "') 
								  UNION ALL
								  SELECT id as parichaya, price as ketebida, 'wat' as phalaphala, remark as sesabida, shonu as tiarikala 
								  FROM weekly_awards_table WHERE userkani = $shonuid  AND date(shonu) = date('" . $date . "') 
								  
								  UNION ALL
								  SELECT id as parichaya, price as ketebida, 'dat' as phalaphala, remark as sesabida, shonu as tiarikala 
								  FROM daily_awards_table WHERE userkani = $shonuid  AND date(shonu) = date('" . $date . "') 
								  UNION ALL
								  SELECT id as parichaya, price as ketebida, 'nmbt' as phalaphala, remark as sesabida, shonu as tiarikala 
								  FROM new_members_bonus_table WHERE userkani = $shonuid  AND date(shonu) = date('" . $date . "') 
								  UNION ALL
								  SELECT id as parichaya, price as ketebida, 'rat' as phalaphala, remark as sesabida, shonu as tiarikala 
								  FROM return_awards_table WHERE userkani = $shonuid  AND date(shonu) = date('" . $date . "') 
								  
								  
								  UNION ALL
								  SELECT id as parichaya, amount as ketebida, 'agcmsn' as phalaphala, level as sesabida, date as tiarikala 
								  FROM to_be_add_comission WHERE user_id = $shonuid   AND date(date) = date('" . $date . "') 
								  
								  
								  
								  UNION ALL
								  SELECT id as parichaya, price as ketebida, 'acht' as phalaphala, remark as sesabida, shonu as tiarikala 
								  FROM aks_comission_history_table WHERE userkani = $shonuid   AND date(shonu) = date('" . $date . "') 
								  
								  
								  UNION ALL
								  SELECT id as parichaya, price as ketebida, 'brdt' as phalaphala, remark as sesabida, shonu as tiarikala 
								  FROM balance_detuct_table WHERE userkani = $shonuid  AND date(shonu) = date('" . $date . "') 
								  
								  ORDER BY tiarikala DESC LIMIT $pageSize OFFSET $samatolana";

			$samasyephalitansa = $conn->query($samasye);

			$samasye_ondu = "SELECT parichaya
								  FROM bajikattuttate WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('" . $date . "')
								  UNION ALL
								  SELECT parichaya
								  FROM bajikattuttate_drei WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('" . $date . "')
                                  UNION ALL
								  SELECT parichaya
								  FROM bajikattuttate_trx WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('" . $date . "')
                                  UNION ALL
								  SELECT kramasankhye as parichaya
								  FROM shonu_kaichila WHERE balakedara = $shonuid AND date(dinankavannuracisi) = date('" . $date . "')
                                  UNION ALL
								  SELECT parichaya
								  FROM bajikattuttate_trx3 WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('" . $date . "')
                                  UNION ALL
								  SELECT parichaya
								  FROM bajikattuttate_trx5 WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('" . $date . "')
                                  UNION ALL
								  SELECT parichaya
								  FROM bajikattuttate_trx10 WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('" . $date . "')
								  UNION ALL
								  SELECT parichaya
								  FROM bajikattuttate_funf WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('" . $date . "')
								  UNION ALL
								  SELECT parichaya
								  FROM bajikattuttate_zehn WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('" . $date . "')
								  UNION ALL
								  SELECT parichaya
								  FROM bajikattuttate_aidudi WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('" . $date . "')
								  UNION ALL
								  SELECT parichaya
								  FROM bajikattuttate_aidudi_drei WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('" . $date . "')
								  UNION ALL
								  SELECT parichaya
								  FROM bajikattuttate_aidudi_funf WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('" . $date . "')
								  UNION ALL
								  SELECT parichaya
								  FROM bajikattuttate_aidudi_zehn WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('" . $date . "')
								  UNION ALL
								  SELECT parichaya
								  FROM bajikattuttate_kemuru WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('" . $date . "')
								  UNION ALL
								  SELECT parichaya
								  FROM bajikattuttate_kemuru_drei WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('" . $date . "')
								  UNION ALL
								  SELECT parichaya
								  FROM bajikattuttate_kemuru_funf WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('" . $date . "')
								  UNION ALL
								  SELECT parichaya
								  FROM bajikattuttate_kemuru_zehn WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('" . $date . "')
								  UNION ALL
								  SELECT macau as parichaya
								  FROM dailysalary WHERE userid = $shonuid AND date(createdate) = date('" . $date . "')
								  UNION ALL
								  SELECT shonu as parichaya
								  FROM thevani WHERE balakedara = $shonuid AND sthiti = 1 AND date(dinankavannuracisi) = date('" . $date . "')
								  UNION ALL
								  SELECT id as parichaya
								  FROM spinrec WHERE user_id = $shonuid AND date(time) = date('" . $date . "')
								  UNION ALL
								  SELECT shonu as parichaya
								  FROM hintegedukolli WHERE balakedara = $shonuid AND date(dinankavannuracisi) = date('" . $date . "') AND madari <> 6
								  UNION ALL
								  SELECT shonu as parichaya
								  FROM hintegedukolli WHERE balakedara = $shonuid AND date(dinankavannuracisi) = date('" . $date . "') AND madari = 6
								  UNION ALL
								  SELECT id as parichaya
								  FROM rebetrec WHERE user_id = $shonuid AND date(created_at) = date('" . $date . "')
								  UNION ALL
								  SELECT dearlord as parichaya
								  FROM cihne WHERE identity = $shonuid AND date(amen) = date('" . $date . "')
								  UNION ALL
								  SELECT id as parichaya
								  FROM viprec WHERE user_id = $shonuid AND date(created_at) = date('" . $date . "')
								  UNION ALL
								  SELECT id as parichaya
								  FROM commission WHERE user_id = $shonuid AND date(created_timestamp) = date('" . $date . "')
								  UNION ALL
								  SELECT id as parichaya
								  FROM egrahcer_sonub WHERE dr = $shonuid AND date(time) = date('" . $date . "')
								   UNION ALL
								  SELECT id as parichaya
								  FROM noitativni_sonub WHERE arthur = $shonuid AND date(time) = date('" . $date . "')
								  UNION ALL
								  SELECT kani as parichaya
								  FROM hodike_balakedara WHERE userkani = $shonuid AND date(shonu) = date('" . $date . "')
								  
								  UNION ALL
								  SELECT id as parichaya
								  FROM recharge_gift_table WHERE userkani = $shonuid AND date(shonu) = date('" . $date . "')
								  UNION ALL
								  SELECT id as parichaya
								  FROM bonus_recharge_table WHERE userkani = $shonuid AND date(shonu) = date('" . $date . "')
								  UNION ALL
								  SELECT id as parichaya
								  FROM first_full_gift_table WHERE userkani = $shonuid AND date(shonu) = date('" . $date . "')
								  UNION ALL
								  SELECT id as parichaya
								  FROM invite_bonus_table WHERE userkani = $shonuid AND date(shonu) = date('" . $date . "')
								  UNION ALL
								  SELECT id as parichaya
								  FROM card_binding_gift_table WHERE userkani = $shonuid AND date(shonu) = date('" . $date . "')
								  UNION ALL
								  SELECT id as parichaya
								  FROM weekly_awards_table WHERE userkani = $shonuid AND date(shonu) = date('" . $date . "')
								  
								  UNION ALL
								  SELECT id as parichaya
								  FROM daily_awards_table WHERE userkani = $shonuid AND date(shonu) = date('" . $date . "')
								  UNION ALL
								  SELECT id as parichaya
								  FROM new_members_bonus_table WHERE userkani = $shonuid AND date(shonu) = date('" . $date . "')
								  UNION ALL
								  SELECT id as parichaya
								  FROM return_awards_table WHERE userkani = $shonuid AND date(shonu) = date('" . $date . "')
								 
							
								  ";
			$samasyephalitansa_ondu = $conn->query($samasye_ondu);

if (!$samasyephalitansa_ondu) {
    echo "<table border='1' cellpadding='10' style='border-collapse:collapse;'>
            <tr><th>Error</th></tr>
            <tr><td>" . $conn->error . "</td></tr>
          </table>";
    exit(); // optional: stop execution
}

			$samasyephalitansa_sankhye = mysqli_num_rows($samasyephalitansa_ondu);
		} else if ($type == 0) {
			$samasye = "SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
								  FROM bajikattuttate WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('".$date."')
								  UNION ALL
								  SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
								  FROM bajikattuttate_drei WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('".$date."')
								  UNION ALL
								  SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala  
FROM bajikattutate_funj  
WHERE byabahakarta = $shonuid;
 AND date(tiarikala) = date('".$date."')
								  UNION ALL
								  SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
								  FROM bajikattuttate_zehn WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('".$date."')
								  UNION ALL
								  SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
								  FROM bajikattuttate_aidudi WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('".$date."')
								  UNION ALL
								  SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
								  FROM bajikattuttate_aidudi_drei WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('".$date."')
								  UNION ALL
								  SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
								  FROM bajikattuttate_aidudi_funf WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('".$date."')
								  UNION ALL
								  SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
								  FROM bajikattuttate_aidudi_zehn WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('".$date."')
								  UNION ALL
								  SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
								  FROM bajikattuttate_kemuru WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('".$date."')
								  UNION ALL
								  SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
								  FROM bajikattuttate_kemuru_drei WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('".$date."')
								  UNION ALL
								  SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
								  FROM bajikattuttate_kemuru_funf WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('".$date."')
								  UNION ALL
								  SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala  
FROM bajikattutate_kemuru_zehn  
WHERE byabahakarta = $shonuid;
 AND date(tiarikala) = date('".$date."')
								  ORDER BY tiarikala DESC LIMIT $pageSize OFFSET $samatolana";							
								$samasyephalitansa = $conn->query($samasye);
								
								$samasye_ondu = "SELECT parichaya
								  FROM bajikattuttate WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('".$date."')
								  UNION ALL
								  SELECT parichaya
								  FROM bajikattuttate_drei WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('".$date."')
								  UNION ALL
								  SELECT parichaya
								  FROM bajikattuttate_funf WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('".$date."')
								  UNION ALL
								  SELECT parichaya
								  FROM bajikattuttate_zehn WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('".$date."')
								  UNION ALL
								  SELECT parichaya
								  FROM bajikattuttate_aidudi WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('".$date."')
								  UNION ALL
								  SELECT parichaya
								  FROM bajikattuttate_aidudi_drei WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('".$date."')
								  UNION ALL
								  SELECT parichaya
								  FROM bajikattuttate_aidudi_funf WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('".$date."')
								  UNION ALL
								  SELECT parichaya
								  FROM bajikattuttate_aidudi_zehn WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('".$date."')
								  UNION ALL
								  SELECT parichaya
								  FROM bajikattuttate_kemuru WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('".$date."')
								  UNION ALL
								  SELECT parichaya
								  FROM bajikattuttate_kemuru_drei WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('".$date."')
								  UNION ALL
								  SELECT parichaya
								  FROM bajikattuttate_kemuru_funf WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('".$date."')
								  UNION ALL
								  SELECT parichaya
								  FROM bajikattuttate_kemuru_zehn WHERE byabaharkarta = $shonuid AND date(tiarikala) = date('".$date."')";
								$samasyephalitansa_ondu = $conn->query($samasye_ondu);
								$samasyephalitansa_sankhye = mysqli_num_rows($samasyephalitansa_ondu);
		}
		else if ($type == 1) {
			$samasye = "SELECT macau, salary, createdate  
FROM dailysalary  
WHERE userid = $shonuid  
AND date(createdate) = date('".$date."')  
ORDER BY macau DESC  
LIMIT $pageSize OFFSET $samatolana;
";
								$samasyephalitansa = $conn->query($samasye);
								
								$samasye_ondu = "SELECT macau  
FROM dailysalary  
WHERE userid = $shonuid  
AND date(createdate) = date('".$date."');
";
								$samasyephalitansa_ondu = $conn->query($samasye_ondu);
								$samasyephalitansa_sankhye = mysqli_num_rows($samasyephalitansa_ondu);
							}
							else if($type == 4){
								$samasye = "SELECT shonu, motta, dinankavannuracisi  
FROM thevani  
WHERE balakedara = $shonuid  
AND sthiti = 1  
AND date(dinankavannuracisi) = date('".$date."')  
ORDER BY dinankavannuracisi DESC  
LIMIT $pageSize OFFSET $samatolana;
";
								$samasyephalitansa = $conn->query($samasye);
								
								$samasye_ondu = "SELECT shonu, motta, dinankavannuracisi  
FROM thevani  
WHERE balakedara = $shonuid  
AND sthiti = 1  
AND date(dinankavannuracisi) = date;
('".$date."')";
								$samasyephalitansa_ondu = $conn->query($samasye_ondu);
								$samasyephalitansa_sankhye = mysqli_num_rows($samasyephalitansa_ondu);
							
						   } else if ($type == 119) {
                              $samasye = "SELECT id, prize, time
                                  FROM spinrec
                                  WHERE user_id = $shonuid AND date(time) = date('" . $date . "')
                                  ORDER BY time DESC LIMIT $pageSize OFFSET $samatolana";
                              $samasyephalitansa = $conn->query($samasye);
    
                              $samasye_ondu = "SELECT id, prize, time
                                  FROM spinrec
                                WHERE user_id = $shonuid AND date(time) = date('" . $date . "')";
                             $samasyephalitansa_ondu = $conn->query($samasye_ondu);
                             $samasyephalitansa_sankhye = mysqli_num_rows($samasyephalitansa_ondu);
							} else if ($type == 12) {
								$samasye = "SELECT kramasankhye, bonus, dinankavannuracisi
									FROM shonu_kaichila
									WHERE balakedara = $shonuid AND date(dinankavannuracisi) = date('" . $date . "')
									ORDER BY time DESC LIMIT $pageSize OFFSET $samatolana";
								$samasyephalitansa = $conn->query($samasye);
	  
								$samasye_ondu = "SELECT kramasankhye, bonus, dinankavannuracisi
									FROM shonu_kaichila
								  WHERE balakedara = $shonuid AND date(dinankavannuracisi) = date('" . $date . "')";
							   $samasyephalitansa_ondu = $conn->query($samasye_ondu);
							   $samasyephalitansa_sankhye = mysqli_num_rows($samasyephalitansa_ondu);
                           }else if($type == 5) {
                                   $samasye = "SELECT shonu, motta, dinankavannuracisi, remarks 
                                               FROM hintegedukolli 
                                               WHERE balakedara = $shonuid 
                                               AND date(dinankavannuracisi) = date('".$date."') 
                                               ORDER BY dinankavannuracisi DESC 
                                               LIMIT $pageSize OFFSET $samatolana";
                                   $samasyephalitansa = $conn->query($samasye);
                                   
                                   $samasye_ondu = "SELECT shonu, motta, dinankavannuracisi, remarks 
                                                    FROM hintegedukolli 
                                                    WHERE balakedara = $shonuid 
                                                    AND date(dinankavannuracisi) = date('".$date."')";
                                   $samasyephalitansa_ondu = $conn->query($samasye_ondu);
                                   $samasyephalitansa_sankhye = mysqli_num_rows($samasyephalitansa_ondu);
                               }

							else if($type == 2){
								$samasye = "SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
								  FROM bajikattuttate WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner' AND date(tiarikala) = date('".$date."')
								  UNION ALL
								  SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
								  FROM bajikattuttate_drei WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner' AND date(tiarikala) = date('".$date."')
								  UNION ALL
								  SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
								  FROM bajikattuttate_funf WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner' AND date(tiarikala) = date('".$date."')
								  UNION ALL
								  SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
								  FROM bajikattuttate_zehn WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner' AND date(tiarikala) = date('".$date."')
								  UNION ALL
								  SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
								  FROM bajikattuttate_aidudi WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner' AND date(tiarikala) = date('".$date."')
								  UNION ALL
								  SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
								  FROM bajikattuttate_aidudi_drei WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner' AND date(tiarikala) = date('".$date."')
								  UNION ALL
								  SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
								  FROM bajikattuttate_aidudi_funf WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner' AND date(tiarikala) = date('".$date."')
								  UNION ALL
								  SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
								  FROM bajikattuttate_aidudi_zehn WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner' AND date(tiarikala) = date('".$date."')
								  UNION ALL
								  SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
								  FROM bajikattuttate_kemuru WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner' AND date(tiarikala) = date('".$date."')
								  UNION ALL
								  SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
								  FROM bajikattuttate_kemuru_drei WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner' AND date(tiarikala) = date('".$date."')
								  UNION ALL
								  SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
								  FROM bajikattuttate_kemuru_funf WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner' AND date(tiarikala) = date('".$date."')
								  UNION ALL
								  SELECT parichaya, ketebida, phalaphala, sesabida, tiarikala
								  FROM bajikattuttate_kemuru_zehn WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner' AND date(tiarikala) = date('".$date."')
								  ORDER BY tiarikala DESC LIMIT $pageSize OFFSET $samatolana";
								$samasyephalitansa = $conn->query($samasye);
								
								$samasye_ondu = "SELECT parichaya
								  FROM bajikattuttate WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner' AND date(tiarikala) = date('".$date."')
								  UNION ALL
								  SELECT parichaya
								  FROM bajikattuttate_drei WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner' AND date(tiarikala) = date('".$date."')
								  UNION ALL
								  SELECT parichaya
								  FROM bajikattuttate_funf WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner' AND date(tiarikala) = date('".$date."')
								  UNION ALL
								  SELECT parichaya
								  FROM bajikattuttate_zehn WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner' AND date(tiarikala) = date('".$date."')
								  UNION ALL
								  SELECT parichaya
								  FROM bajikattuttate_aidudi WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner' AND date(tiarikala) = date('".$date."')
								  UNION ALL
								  SELECT parichaya
								  FROM bajikattuttate_aidudi_drei WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner' AND date(tiarikala) = date('".$date."')
								  UNION ALL
								  SELECT parichaya
								  FROM bajikattuttate_aidudi_funf WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner' AND date(tiarikala) = date('".$date."')
								  UNION ALL
								  SELECT parichaya
								  FROM bajikattuttate_aidudi_zehn WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner' AND date(tiarikala) = date('".$date."')
								  UNION ALL
								  SELECT parichaya
								  FROM bajikattuttate_kemuru WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner' AND date(tiarikala) = date('".$date."')
								  UNION ALL
								  SELECT parichaya
								  FROM bajikattuttate_kemuru_drei WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner' AND date(tiarikala) = date('".$date."')
								  UNION ALL
								  SELECT parichaya
								  FROM bajikattuttate_kemuru_funf WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner' AND date(tiarikala) = date('".$date."')
								  UNION ALL
								  SELECT parichaya
								  FROM bajikattuttate_kemuru_zehn WHERE byabaharkarta = $shonuid AND phalaphala = 'gagner' AND date(tiarikala) = date('".$date."')";
								$samasyephalitansa_ondu = $conn->query($samasye_ondu);
								$samasyephalitansa_sankhye = mysqli_num_rows($samasyephalitansa_ondu);
							}
							
		else if (in_array($type, [3, 8, 10, 13, 14, 20, 21, 22, 25, 107, 124, 118, 117, 115])) {
			$samasye = getBonusQuery($type, $shonuid, $pageSize, $samatolana, $date);
			$samasyephalitansa = $conn->query($samasye);

			$samasye_ondu = getBonusQueryCnt($type, $shonuid, $date);
			$samasyephalitansa_ondu = $conn->query($samasye_ondu);
			$samasyephalitansa_sankhye = mysqli_num_rows($samasyephalitansa_ondu);
		} else if ($type == 14) {
			$samasye = "\x53\x45\x4c\x45\x43\x54\x20\x69\x64\x2c\x20\x73\x74\x75\x72\x67\x69\x73\x2c\x20\x74\x69\x6d\x65\xd\xa\x9\x9\x9\x9\x9\x9\x9\x9\x20\x20\x46\x52\x4f\x4d\x20\x65\x67\x72\x61\x68\x63\x65\x72\x5f\x73\x6f\x6e\x75\x62\x20\x57\x48\x45\x52\x45\x20\x64\x72\x20\x3d\x20$shonuid\x20\x41\x4e\x44\x20\x64\x61\x74\x65\x28\x74\x69\x6d\x65\x29\x20\x3d\x20date('" . $date . "')
								 \x20\x4f\x52\x44\x45\x52\x20\x42\x59\x20\x74\x69\x6d\x65\x20\x44\x45\x53\x43\x20\x4c\x49\x4d\x49\x54\x20$pageSize\x20\x4f\x46\x46\x53\x45\x54\x20$samatolana";
			$samasyephalitansa = $conn->query($samasye);

			$samasye_ondu = "\x53\x45\x4c\x45\x43\x54\x20\x69\x64\xd\xa\x9\x9\x9\x9\x9\x9\x9\x9\x20\x20\x46\x52\x4f\x4d\x20\x65\x67\x72\x61\x68\x63\x65\x72\x5f\x73\x6f\x6e\x75\x62\x20\x57\x48\x45\x52\x45\x20\x64\x72\x20\x3d\x20$shonuid\x20\x41\x4e\x44\x20\x64\x61\x74\x65\x28\x74\x69\x6d\x65\x29\x20\x3d\x20date('" . $date . "')";
			$samasyephalitansa_ondu = $conn->query($samasye_ondu);
			$samasyephalitansa_sankhye = mysqli_num_rows($samasyephalitansa_ondu);
		}
	}

	if (in_array($type, [-1, 0, 1, 4, 119, 5, 2, 14, 3, 8, 10, 13, 14, 20, 21, 22, 25, 107, 124, 118, 117, 115])) {
		if ($samasyephalitansa->num_rows > 0) {
			$i = 0;
			$bonusTypes = [
				3 => ["Red envelope", '8003'],
				8 => ["Agent's red envelope", '8008'],
				10 => ["Deposit Gift", '8010'],
				13 => ["Bonus", '8013'],
				14 => ["First deposit bonus", '8014'],
				20 => ["Mission rewards", '8020'],
				21 => ["Game Moved in", '8021'],
				22 => ["Game Moved out", '8022'],
				25 => ["Bank binding bonus", '8025'],
				107 => ["Weekly Award", '8107'],
				124 => ["Join channel rewards", '8124'],
				118 => ["Daily Awards", '8118'],
				117 => ["New members get bonuses by playing games", '8117'],
				115 => ["Return Awards", '8115'],
			];
			while ($row = $samasyephalitansa->fetch_assoc()) {
				if($type == 1){
										$data['list'][$i]['amount'] = $row['salary'];
										$data['list'][$i]['type'] = 1;
										$data['list'][$i]['typeName'] = 'Salary';
										$data['list'][$i]['typeNameCode'] = '8001';
										$data['list'][$i]['orderNum'] = $row['macau'];
										$data['list'][$i]['addTime'] = $row['createdate'];
										$data['list'][$i]['remark'] = '';
									}
									else if($type == 4){
										$data['list'][$i]['amount'] = $row['motta'];
										$data['list'][$i]['type'] = 4;
										$data['list'][$i]['typeName'] = 'Deposit';
										$data['list'][$i]['typeNameCode'] = '8004';
										$data['list'][$i]['orderNum'] = $row['shonu'];
										$data['list'][$i]['addTime'] = $row['dinankavannuracisi'];
										$data['list'][$i]['remark'] = '';
									}
									else if($type == 119){
										$data['list'][$i]['amount'] = $row['prize'];
										$data['list'][$i]['type'] = 119;
										$data['list'][$i]['typeName'] = 'spin';
										$data['list'][$i]['typeNameCode'] = '8119';
										$data['list'][$i]['orderNum'] = $row['id'];
										$data['list'][$i]['addTime'] = $row['time'];
										$data['list'][$i]['remark'] = '';
									}else if($type == 12){
										$data['list'][$i]['amount'] = $row['bonus'];
										$data['list'][$i]['type'] = 12;
										$data['list'][$i]['typeName'] = 'spin';
										$data['list'][$i]['typeNameCode'] = '8012';
										$data['list'][$i]['orderNum'] = $row['kramasankhye'];
										$data['list'][$i]['addTime'] = $row['dinankavannuracisi'];
										$data['list'][$i]['remark'] = '';
									}
									else if($type == 5){
										$data['list'][$i]['amount'] = $row['motta'];
										$data['list'][$i]['type'] = 5;
										$data['list'][$i]['typeName'] = 'Withdraw';
										$data['list'][$i]['typeNameCode'] = '8005';
										$data['list'][$i]['orderNum'] = $row['shonu'];
										$data['list'][$i]['addTime'] = $row['dinankavannuracisi'];
										$data['list'][$i]['remark'] = $row['remarks'];
									}
									else if($type == 2){
										$data['list'][$i]['amount'] = $row['sesabida'];
										$data['list'][$i]['type'] = 2;
										$data['list'][$i]['typeName'] = 'Win';
										$data['list'][$i]['typeNameCode'] = '8002';
										$data['list'][$i]['orderNum'] = $row['parichaya'];
										$data['list'][$i]['addTime'] = $row['tiarikala'];
										$data['list'][$i]['remark'] = '';
									}
									else if($type == 3){
										$data['list'][$i]['amount'] = $row['price'];
										$data['list'][$i]['type'] = 3;
										$data['list'][$i]['typeName'] = 'Red Envelope';
										$data['list'][$i]['typeNameCode'] = '8003';
										$data['list'][$i]['orderNum'] = $row['kani'];
										$data['list'][$i]['addTime'] = $row['shonu'];
										$data['list'][$i]['remark'] = '';
									}
									else if($type == 14){
										$data['list'][$i]['amount'] = $row['sturgis'] == 1 ? 60 : ($row['sturgis'] == 2 ? 20 : ($row['sturgis'] == 3 ? 150 : ($row['sturgis'] == 4 ? 300 : 0)));
										$data['list'][$i]['amount'] = $row['sturgis'] == 5 ? 600 : ($row['sturgis'] == 6 ? 2000 : ($row['sturgis'] == 7 ? 5000 : ($row['sturgis'] == 8 ? 10000 : $data['list'][$i]['amount']))); 
										$data['list'][$i]['type'] = 14;
										$data['list'][$i]['typeName'] = 'First deposit bonus';
										$data['list'][$i]['typeNameCode'] = '8014';
										$data['list'][$i]['orderNum'] = $row['id'];
										$data['list'][$i]['addTime'] = $row['time'];
										$data['list'][$i]['remark'] = '';
									}
									else{
										if($row['phalaphala'] == 'gagner'){
											$data['list'][$i]['amount'] = $row['sesabida'];
											$data['list'][$i]['type'] = 2;
											$data['list'][$i]['typeName'] = 'Win';
											$data['list'][$i]['typeNameCode'] = '8002';
											$data['list'][$i]['orderNum'] = $row['parichaya'];
											$data['list'][$i]['addTime'] = $row['tiarikala'];
											$data['list'][$i]['remark'] = '';
										}
										else if($row['phalaphala'] == 'ds'){
											$data['list'][$i]['amount'] = $row['ketebida'];
											$data['list'][$i]['type'] = 1;
											$data['list'][$i]['typeName'] = 'Salary';
											$data['list'][$i]['typeNameCode'] = '8001';
											$data['list'][$i]['orderNum'] = $row['parichaya'];
											$data['list'][$i]['addTime'] = $row['tiarikala'];
											$data['list'][$i]['remark'] = '';
										}
										else if($row['phalaphala'] == 'rc'){
											$data['list'][$i]['amount'] = $row['ketebida'];
											$data['list'][$i]['type'] = 4;
											$data['list'][$i]['typeName'] = 'Deposit';
											$data['list'][$i]['typeNameCode'] = '8004';
											$data['list'][$i]['orderNum'] = $row['parichaya'];
											$data['list'][$i]['addTime'] = $row['tiarikala'];
											$data['list'][$i]['remark'] = '';
										}
										else if($row['phalaphala'] == 'rb'){
											$data['list'][$i]['amount'] = (int)$row['ketebida'];
											$data['list'][$i]['type'] = 119;
											$data['list'][$i]['typeName'] = 'spin';
											$data['list'][$i]['typeNameCode'] = '8119';
											$data['list'][$i]['orderNum'] = $row['parichaya'];
											$data['list'][$i]['addTime'] = $row['tiarikala'];
											$data['list'][$i]['remark'] = '';
                                          }else if($row['phalaphala'] == 'sb'){
											$data['list'][$i]['amount'] = (int)$row['ketebida'];
											$data['list'][$i]['type'] = 12;
											$data['list'][$i]['typeName'] = 'Signup';
											$data['list'][$i]['typeNameCode'] = '8012';
											$data['list'][$i]['orderNum'] = $row['parichaya'];
											$data['list'][$i]['addTime'] = $row['tiarikala'];
											$data['list'][$i]['remark'] = '';
										}else if($row['phalaphala'] == 'orb'){
											$data['list'][$i]['amount'] = $row['ketebida'];
											$data['list'][$i]['type'] = 102;
											$data['list'][$i]['typeName'] = 'rebet';
											$data['list'][$i]['typeNameCode'] = '8102';
											$data['list'][$i]['orderNum'] = $row['parichaya'];
											$data['list'][$i]['addTime'] = $row['tiarikala'];
											$data['list'][$i]['remark'] = '';
										}else if($row['phalaphala'] == 'reftask'){
											$data['list'][$i]['amount'] = $row['ketebida'];
											$data['list'][$i]['type'] = 20;
											$data['list'][$i]['typeName'] = 'refer';
											$data['list'][$i]['typeNameCode'] = '8020';
											$data['list'][$i]['orderNum'] = $row['parichaya'];
											$data['list'][$i]['addTime'] = $row['tiarikala'];
											$data['list'][$i]['remark'] = '';
										}else if($row['phalaphala'] == 'lvlup'){
                                         if ($row['sesabida'] == 1) {
                                            $data['list'][$i]['type'] = 29;
                                            $data['list'][$i]['typeNameCode'] = '8029';
                                        } else if ($row['sesabida'] == 2) {
                                            $data['list'][$i]['type'] = 30; 
                                            $data['list'][$i]['typeNameCode'] = '8030';
                                        } else {
    
                                           $data['list'][$i]['type'] = null;
                                        }
                                          $data['list'][$i]['amount'] = $row['ketebida']; 
                                          $data['list'][$i]['typeName'] = 'VIP'; 
                                          $data['list'][$i]['orderNum'] = $row['parichaya'];
                                          $data['list'][$i]['addTime'] = $row['tiarikala'];
                                          $data['list'][$i]['remark'] = 'VIP' ;

										}else if($row['phalaphala'] == 'atb'){
											$data['list'][$i]['amount'] = $row['ketebida'];
											$data['list'][$i]['type'] = 7;
											$data['list'][$i]['typeName'] = 'attendence';
											$data['list'][$i]['typeNameCode'] = '8007';
											$data['list'][$i]['orderNum'] = $row['parichaya'];
											$data['list'][$i]['addTime'] = $row['tiarikala'];
											$data['list'][$i]['remark'] = '';
										}else if($row['phalaphala'] == 'cmd'){
											$data['list'][$i]['amount'] = $row['ketebida'];
											$data['list'][$i]['type'] = 1;
											$data['list'][$i]['typeName'] = 'attendence';
											$data['list'][$i]['typeNameCode'] = '8001';
											$data['list'][$i]['orderNum'] = $row['parichaya'];
											$data['list'][$i]['addTime'] = $row['tiarikala'];
											$data['list'][$i]['remark'] = '';
										}
										else if($row['phalaphala'] == 'wd'){
											$data['list'][$i]['amount'] = $row['ketebida'];
											$data['list'][$i]['type'] = 5;
											$data['list'][$i]['typeName'] = 'Withdraw';
											$data['list'][$i]['typeNameCode'] = '8005';
											$data['list'][$i]['orderNum'] = $row['parichaya'];
											$data['list'][$i]['addTime'] = $row['tiarikala'];
											$data['list'][$i]['remark'] = $row['sesabida'];
										}
										else if($row['phalaphala'] == 'arert'){                    // agent_red_envelope_recharge_table - > arert            - error deti h union ka hta diya h isko maine
											$data['list'][$i]['amount'] = $row['ketebida'];
											$data['list'][$i]['type'] = 3;
											$data['list'][$i]['typeName'] = 'Agent Red Envelope';
											$data['list'][$i]['typeNameCode'] = '8008';
											$data['list'][$i]['orderNum'] = $row['parichaya'];
											$data['list'][$i]['addTime'] = $row['tiarikala'];
											$data['list'][$i]['remark'] = $row['sesabida'];
										}
										else if($row['phalaphala'] == 're'){
											$data['list'][$i]['amount'] = $row['ketebida'];
											$data['list'][$i]['type'] = 3;
											$data['list'][$i]['typeName'] = 'Red Envelope';
											$data['list'][$i]['typeNameCode'] = '8003';
											$data['list'][$i]['orderNum'] = $row['parichaya'];
											$data['list'][$i]['addTime'] = $row['tiarikala'];
											$data['list'][$i]['remark'] = $row['sesabida'];
										}
										else if($row['phalaphala'] == 'ibt'){                        // invite_bonus_table
											$data['list'][$i]['amount'] = $row['ketebida'];
											$data['list'][$i]['type'] = 3;
											$data['list'][$i]['typeName'] = 'Invite Bonus';
											$data['list'][$i]['typeNameCode'] = '8020';
											$data['list'][$i]['orderNum'] = $row['parichaya'];
											$data['list'][$i]['addTime'] = $row['tiarikala'];
											$data['list'][$i]['remark'] = $row['sesabida'];
										}
										else if($row['phalaphala'] == 'abt'){                        // agent_bonus_table   as Agent Bonus
											$data['list'][$i]['amount'] = $row['ketebida'];
											$data['list'][$i]['type'] = 3;
											$data['list'][$i]['typeName'] = 'Agent Bonus';
											$data['list'][$i]['typeNameCode'] = '8008';
											$data['list'][$i]['orderNum'] = $row['parichaya'];
											$data['list'][$i]['addTime'] = $row['tiarikala'];
											$data['list'][$i]['remark'] = $row['sesabida'];
										}
										else if($row['phalaphala'] == 'brt'){                       // bonus_recharge_table as Recharge Bonus
											$data['list'][$i]['amount'] = $row['ketebida'];
											$data['list'][$i]['type'] = 13;
											$data['list'][$i]['typeName'] = 'Recharge Bonus';
											$data['list'][$i]['typeNameCode'] = '8013';
											$data['list'][$i]['orderNum'] = $row['parichaya'];
											$data['list'][$i]['addTime'] = $row['tiarikala'];
											$data['list'][$i]['remark'] = $row['sesabida'];
										}
										else if($row['phalaphala'] == 'ffgt'){                    //    first_full_gift_table    ad First deposit bonus
											$data['list'][$i]['amount'] = $row['ketebida'];
											$data['list'][$i]['type'] = 14;
											$data['list'][$i]['typeName'] = 'First deposit bonus';
											$data['list'][$i]['typeNameCode'] = '8014';
											$data['list'][$i]['orderNum'] = $row['parichaya'];
											$data['list'][$i]['addTime'] = $row['tiarikala'];
											$data['list'][$i]['remark'] = $row['sesabida'];
										}
										else if($row['phalaphala'] == 'cbgt'){                  // card_binding_gift_table as Bank Binding Gift
											$data['list'][$i]['amount'] = $row['ketebida'];
											$data['list'][$i]['type'] = 25;
											$data['list'][$i]['typeName'] = 'Bank Binding Gift';
											$data['list'][$i]['typeNameCode'] = '8025';
											$data['list'][$i]['orderNum'] = $row['parichaya'];
											$data['list'][$i]['addTime'] = $row['tiarikala'];
											$data['list'][$i]['remark'] = $row['sesabida'];
										}
										else if($row['phalaphala'] == 'wat'){                   // weekly_awards_table as Mission Rewards
											$data['list'][$i]['amount'] = $row['ketebida'];
											$data['list'][$i]['type'] = 107;
											$data['list'][$i]['typeName'] = 'Mission Rewards';
											$data['list'][$i]['typeNameCode'] = '8107';
											$data['list'][$i]['orderNum'] = $row['parichaya'];
											$data['list'][$i]['addTime'] = $row['tiarikala'];
											$data['list'][$i]['remark'] = $row['sesabida'];
										}
										
										 
										else if($row['phalaphala'] == 'dat'){                    //    daily_awards_table    ad Daily Awards
											$data['list'][$i]['amount'] = $row['ketebida'];
											$data['list'][$i]['type'] = 118;
											$data['list'][$i]['typeName'] = 'Daily Awards';
											$data['list'][$i]['typeNameCode'] = '8118';
											$data['list'][$i]['orderNum'] = $row['parichaya'];
											$data['list'][$i]['addTime'] = $row['tiarikala'];
											$data['list'][$i]['remark'] = $row['sesabida'];
										}
										else if($row['phalaphala'] == 'nmbt'){                  // new_members_bonus_table as ew members get bonuses by playing games
											$data['list'][$i]['amount'] = $row['ketebida'];
											$data['list'][$i]['type'] = 117;
											$data['list'][$i]['typeName'] = 'New members get bonuses by playing games';
											$data['list'][$i]['typeNameCode'] = '8117'; 
											$data['list'][$i]['orderNum'] = $row['parichaya'];
											$data['list'][$i]['addTime'] = $row['tiarikala'];
											$data['list'][$i]['remark'] = $row['sesabida'];
										}
										else if($row['phalaphala'] == 'rat'){                   // return_awards_table as Return Awards 
											$data['list'][$i]['amount'] = $row['ketebida'];
											$data['list'][$i]['type'] = 115;
											$data['list'][$i]['typeName'] = 'Return Awards';
											$data['list'][$i]['typeNameCode'] = '8115';
											$data['list'][$i]['orderNum'] = $row['parichaya'];
											$data['list'][$i]['addTime'] = $row['tiarikala'];
											$data['list'][$i]['remark'] = $row['sesabida'];
										}
										else if($row['phalaphala'] == 'brdt'){                   // balance_detuct_table as Bonus deduction
											$data['list'][$i]['amount'] = $row['ketebida'];
											$data['list'][$i]['type'] = 100;
											$data['list'][$i]['typeName'] = 'Bonus deduction';
											$data['list'][$i]['typeNameCode'] = '8100';
											$data['list'][$i]['orderNum'] = $row['parichaya'];
											$data['list'][$i]['addTime'] = $row['tiarikala'];
											$data['list'][$i]['remark'] = $row['sesabida'];
										}
										else if($row['phalaphala'] == 'ptreward'){                   // partner_rewards as Bonus deduction
											$data['list'][$i]['amount'] = $row['ketebida'];
											$data['list'][$i]['type'] = 122;
											$data['list'][$i]['typeName'] = 'Partner Rewards';
											$data['list'][$i]['typeNameCode'] = '8122';
											$data['list'][$i]['orderNum'] = $row['parichaya'];
											$data['list'][$i]['addTime'] = $row['tiarikala'];
											$data['list'][$i]['remark'] = $row['sesabida'];
										}
										else if($row['phalaphala'] == 'agcmsn'){                   // to_be_add_comission as Agent commission
								// 			$data['list'][$i]['amount'] = $row['ketebida'];
								// 			$data['list'][$i]['type'] = 1;
								// 			$data['list'][$i]['typeName'] = 'Agent commission';
								// 			$data['list'][$i]['typeNameCode'] = '8001';
								// 			$data['list'][$i]['orderNum'] = $row['parichaya'];
								// 			$data['list'][$i]['addTime'] = $row['tiarikala'];
								// 			$data['list'][$i]['remark'] = $row['sesabida'];
										}
										
									    else if($row['phalaphala'] == 'apigbmi'){                   // aks_amount_in_out_table as Game transfer out
											$data['list'][$i]['amount'] = $row['ketebida'];
											$data['list'][$i]['type'] = 21;
											$data['list'][$i]['typeName'] = 'Amount transfer out from API wallet';
											$data['list'][$i]['typeNameCode'] = '8021';
											$data['list'][$i]['orderNum'] = $row['parichaya'];
											$data['list'][$i]['addTime'] = $row['tiarikala'];
											$data['list'][$i]['remark'] = $row['sesabida'];
										}
										
										else if($row['phalaphala'] == 'apigbmo'){                   // aks_amount_in_out_table as Game transfer out
											$data['list'][$i]['amount'] = $row['ketebida'];
											$data['list'][$i]['type'] = 22;
											$data['list'][$i]['typeName'] = 'Amount transfer in from API wallet';
											$data['list'][$i]['typeNameCode'] = '8022';
											$data['list'][$i]['orderNum'] = $row['parichaya'];
											$data['list'][$i]['addTime'] = $row['tiarikala'];
											$data['list'][$i]['remark'] = $row['sesabida'];
										}
										
										else if($row['phalaphala'] == 'acht'){                   // aks_comission_history_table as Agent commission
											$data['list'][$i]['amount'] = $row['ketebida'];
											$data['list'][$i]['type'] = 1;
											$data['list'][$i]['typeName'] = 'Agent commission';
											$data['list'][$i]['typeNameCode'] = '8001';
											$data['list'][$i]['orderNum'] = $row['parichaya'];
											$data['list'][$i]['addTime'] = $row['tiarikala'];
											$data['list'][$i]['remark'] = $row['sesabida'];
										}
										
										
										
										
										
										else if($row['phalaphala'] == 'frc') {
                                        $ketebida = (int) $row['ketebida']; // Ensure it's an integer
                                         // Fetch reward amounts from tbl_firstdepositreward
                                              $rewardAmounts = [];
                                          $query = "SELECT id, rewardAmount FROM tbl_firstdepositreward";
                                                 $result = mysqli_query($conn, $query);
    
                                         while ($rewardRow = mysqli_fetch_assoc($result)) {
                                                $rewardAmounts[$rewardRow['id']] = $rewardRow['rewardAmount'];
                                                                                    }
                                               // Assign amount based on ketebida
                                               $data['list'][$i]['amount'] = $rewardAmounts[$ketebida] ?? 0; 

                                             // Other details
                                             $data['list'][$i]['type'] = 14;
                                             $data['list'][$i]['typeName'] = 'First recharge';
                                             $data['list'][$i]['typeNameCode'] = '8014';
                                             $data['list'][$i]['orderNum'] = $row['parichaya'];
                                             $data['list'][$i]['addTime'] = $row['tiarikala'];
                                                    $data['list'][$i]['remark'] = '';
                                            } 
                                            
                                            // Custom First Deposit Bonus section  End //

										else{
											$data['list'][$i]['amount'] = $row['ketebida'];
											$data['list'][$i]['type'] = 0;
											$data['list'][$i]['typeName'] = 'Bet';
											$data['list'][$i]['typeNameCode'] = '8000';
											$data['list'][$i]['orderNum'] = $row['parichaya'];
											$data['list'][$i]['addTime'] = $row['tiarikala'];
											$data['list'][$i]['remark'] = '0';
										}
									}								
									$i++;
			}
			$res['data'] = $data;
			$data['pageNo'] = (int) $pageNo;
			$data['totalPage'] = ceil($samasyephalitansa_sankhye / 10);
			$data['totalCount'] = $samasyephalitansa_sankhye;
		} else {
			$data['list'] = [];
			$data['pageNo'] = (int) $pageNo;
			$data['totalPage'] = 0;
			$data['totalCount'] = 0;
		}
	} else {
		$data['list'] = [];
		$data['pageNo'] = (int) $pageNo;
		$data['totalPage'] = 0;
		$data['totalCount'] = 0;
	}
	return $data;
}

?>