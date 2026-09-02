<?php
// vip.php (defensive fixed)

if (!isset($shnunc)) $shnunc = date("Y-m-d H:i:s");

// require $byabaharkarta and $totalamount to be set by caller
if (!isset($byabaharkarta) || !isset($totalamount) || !isset($conn)) {
    // nothing to do
    return;
}

$uid = intval($byabaharkarta);
$tot = floatval($totalamount);

// read vip row
$vipQuery = $conn->prepare("SELECT expe, lvl FROM vip WHERE userid = ?");
$vipQuery->bind_param("i", $uid);
$vipQuery->execute();
$vipQuery->store_result();

if ($vipQuery->num_rows > 0) {
    $vipQuery->bind_result($expe_db, $lvl_db);
    $vipQuery->fetch();
    $vipQuery->close();

    $newExpe = floatval($expe_db) + $tot;
    $orlvl = intval($lvl_db);
    $lvl = $orlvl;

    // compute level by thresholds
    if ($newExpe >= 20000000) $lvl = 5;
    else if ($newExpe >= 4000000) $lvl = 4;
    else if ($newExpe >= 400000) $lvl = 3;
    else if ($newExpe >= 30000) $lvl = 2;
    else if ($newExpe >= 3000) $lvl = 1;
    else $lvl = 0;

    $diff = $lvl - $orlvl;
    $giveamt = 0;
    // award amounts based on diff (replicates old logic sums)
    if ($diff > 0) {
        // compute incremental awards per level gained
        $rewards = [
            1 => [60,30],
            2 => [180,90],
            3 => [690,290],
            4 => [1890,890],
            5 => [6900,1890]
        ];
        for ($d = 1; $d <= $diff; $d++) {
            $lv = $orlvl + $d;
            if (isset($rewards[$lv])) $giveamt += array_sum($rewards[$lv]);
        }
    }

    // update vip table
    $upd = $conn->prepare("UPDATE vip SET expe = ?, lvl = ?, createdate = ? WHERE userid = ?");
    $upd->bind_param("dssi", $newExpe, $lvl, $shnunc, $uid);
    $upd->execute();
    $upd->close();

    // give reward if any
    if ($giveamt > 0) {
        $upd2 = $conn->prepare("UPDATE shonu_kaichila SET motta = motta + ? WHERE balakedara = ?");
        $upd2->bind_param("di", $giveamt, $uid);
        $upd2->execute();
        $upd2->close();
    }

} else {
    // insert new vip record
    $newExpe = $tot;
    $lvl = 0;
    if ($newExpe >= 20000000) $lvl = 5;
    else if ($newExpe >= 4000000) $lvl = 4;
    else if ($newExpe >= 400000) $lvl = 3;
    else if ($newExpe >= 30000) $lvl = 2;
    else if ($newExpe >= 3000) $lvl = 1;

    $giveamt = 0;
    // aggregate all awards up to lvl
    $awards_map = [
        1 => [60,30],
        2 => [180,90],
        3 => [690,290],
        4 => [1890,890],
        5 => [6900,1890]
    ];
    for ($i = 1; $i <= $lvl; $i++) {
        if (isset($awards_map[$i])) $giveamt += array_sum($awards_map[$i]);
    }

    $ins = $conn->prepare("INSERT INTO vip (userid, expe, lvl, createdate) VALUES (?, ?, ?, ?)");
    $ins->bind_param("idii", $uid, $newExpe, $lvl, $shnunc);
    // Note: createdate column type might be varchar/datetime; adjust bind types if necessary
    $ins->execute();
    $ins->close();

    if ($giveamt > 0) {
        $upd2 = $conn->prepare("UPDATE shonu_kaichila SET motta = motta + ? WHERE balakedara = ?");
        $upd2->bind_param("di", $giveamt, $uid);
        $upd2->execute();
        $upd2->close();
    }
}
?>
