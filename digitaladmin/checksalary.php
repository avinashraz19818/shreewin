<?php
session_start();
if (empty($_SESSION['unohs'])) {
    header("location: api/login.php?msg=unauthorized");
}
?>
<?php
include("api/conn.php");
date_default_timezone_set("Asia/Kolkata");

// === CRON CONTROL START ===
$allowed_days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']; // Added 'Sunday'
$allowed_minutes = range(0, 59); // No change needed here
$allowed_months = ['April', 'May', 'June', 'August']; // Added 'August'

$day = date('l');
$minute = (int)date('i');
$month = date('F');

if (!in_array($day, $allowed_days) || !in_array($minute, $allowed_minutes) || !in_array($month, $allowed_months)) {
    exit("⛔ Cron blocked: $day $minute $month");
}
// === CRON CONTROL END ===

$userquery = "SELECT id, owncode, mobile FROM shonu_subjects WHERE status='1'";
$exeuser = $conn->query($userquery);

$tem = date("Y-m-d H:i:s");
$newDate = date("Y-m-d"); 

while ($getsingleuser = mysqli_fetch_array($exeuser)) {
    $totalsucrech = 0;
    $totalfailrech = 0;
    $tsruser = [];
    $tfruser = [];
    $totalsucbet = 0;
    $tsbuser = [];
    $totalfailbet = 0;
    $tfbuser = [];
    $salary = 0;
    $ttrech = 0;

    $owncode = $getsingleuser['owncode'];
    $userid = $getsingleuser['id'];
    $mobile = $getsingleuser['mobile'];

    $getAgent = mysqli_query($conn, "SELECT salary, minbet, minrecharge, minreferrals FROM tb_agent WHERE mobile='$mobile' LIMIT 1");
    $agent = mysqli_fetch_array($getAgent);

    $percent = $agent['salary'] ?? 3;
    $minbet = $agent['minbet'] ?? 300;
    $minrecharge = $agent['minrecharge'] ?? 300;
    $minreferrals = $agent['minreferrals'] ?? 3;

    echo "<hr><b>USER ID: $userid</b><br>";

    $selectreferral = mysqli_query($conn, "SELECT id FROM shonu_subjects WHERE code='$owncode'");
    $totalreferral = mysqli_num_rows($selectreferral);
    echo "Total Referrals: $totalreferral<br>";
    echo "Minimum Referrals Required: $minreferrals<br>";

    if ($totalreferral >= $minreferrals) {
        while ($getreferraldata = mysqli_fetch_array($selectreferral)) {
            $referralid = $getreferraldata['id'];

            $selectrecharge = mysqli_query($conn, "SELECT shonu FROM thevani WHERE balakedara='$referralid' AND sthiti='1' LIMIT 1");
            if (mysqli_num_rows($selectrecharge) > 0) {
                $tsruser[] = $referralid;
                $totalsucrech++;

                $sumchar = mysqli_query($conn, "SELECT SUM(motta) AS recsum FROM thevani WHERE balakedara='$referralid' AND sthiti='1' AND DATE(dinankavannuracisi)=DATE('$newDate')");
                $sumchar_f = mysqli_fetch_array($sumchar);
                $recsum = $sumchar_f['recsum'] ?? 0;
                $ttrech += $recsum;

                $ref_tbet_total = 0;
                $bet_tables = [
                    "bajikattuttate", "bajikattuttate_drei", "bajikattuttate_funf", "bajikattuttate_zehn",
                    "bajikattuttate_aidudi", "bajikattuttate_aidudi_drei", "bajikattuttate_aidudi_funf", "bajikattuttate_aidudi_zehn",
                    "bajikattuttate_kemuru", "bajikattuttate_kemuru_drei", "bajikattuttate_kemuru_funf", "bajikattuttate_kemuru_zehn"
                ];
                foreach ($bet_tables as $table) {
                    $sel = mysqli_query($conn, "SELECT SUM(ketebida) as total FROM $table WHERE byabaharkarta='$referralid' AND DATE(tiarikala)=DATE('$newDate')");
                    $fet = mysqli_fetch_array($sel);
                    $ref_tbet_total += $fet['total'] ?? 0;
                }

                if ($ref_tbet_total >= $minbet) {
                    $tsbuser[] = $referralid;
                    $totalsucbet++;
                } else {
                    $tfbuser[] = $referralid;
                    $totalfailbet++;
                }
            } else {
                $tfruser[] = $referralid;
                $totalfailrech++;
            }
        }

        echo "Success Recharge Users: " . count($tsruser) . "<br>";
        echo "Failed Recharge Users: " . count($tfruser) . "<br>";
        echo "Success Bet Users: " . count($tsbuser) . "<br>";
        echo "Failed Bet Users: " . count($tfbuser) . "<br>";
        echo "Total Recharge: ₹" . number_format($ttrech) . "<br>";
        echo "Success Bets: $totalsucbet<br>";

        if ($totalsucbet >= $minreferrals && $ttrech >= $minrecharge) {
            $salary = round(($ttrech * $percent) / 100);
        }

        echo "💰 Salary Percent: $percent%<br>";
        echo "💵 Calculated Salary: ₹" . number_format($salary) . "<br>";

        if ($salary > 0) {
            $up = mysqli_query($conn, "SELECT motta FROM shonu_kaichila WHERE balakedara='$userid'");
            $rup = mysqli_fetch_array($up);
            $current_wallet = $rup['motta'] ?? 0;
            $addmoney = $current_wallet + $salary;

            $wallet_updated = mysqli_query($conn, "UPDATE shonu_kaichila SET motta='$addmoney' WHERE balakedara='$userid'");
            echo $wallet_updated ? "Wallet Updated: ₹" . number_format($addmoney) . "<br>" : "Wallet Update Failed: " . mysqli_error($conn) . "<br>";

            $insert = mysqli_query($conn, "INSERT INTO dailysalary(userid, totalsucrech, totalfailrech, tsruser, tfruser, totalsucbet, tsbuser, totalfailbet, tfbuser, salary, createdate)
                VALUES('$userid', '$totalsucrech', '$totalfailrech', '" . json_encode($tsruser) . "', '" . json_encode($tfruser) . "', '$totalsucbet', '" . json_encode($tsbuser) . "', '$totalfailbet', '" . json_encode($tfbuser) . "', '$salary', '$tem')");
            echo $insert ? "Salary Inserted<br>" : "Salary Insert Failed: " . mysqli_error($conn) . "<br>";
        } else {
            echo "No Salary Given (Conditions not met)<br>";
        }
    } else {
        echo "Not enough referrals.<br>";
    }
}
?>
