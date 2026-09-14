<?php include ("../serive/samparka.php"); ?>

<?php

$config = require 'bmconfig.php';


$data = $_POST;



$mchOrderNo = $data['order_id'];


$checkamt = mysqli_query($conn, "SELECT motta, balakedara FROM thevani WHERE dharavahi = '".$mchOrderNo."' AND sthiti = '0'");

if (!$checkamt) {
    logError("Database query error: " . mysqli_error($conn));
    echo json_encode([
        "message" => "fail(database error)",
        "status" => false,
    ]);
    exit;
}

$checkamtrow = mysqli_num_rows($checkamt);

if ($checkamtrow >= 1) {
    $checkamtar = mysqli_fetch_array($checkamt);
    $motta = $checkamtar['motta'];
    $shonuid = $checkamtar['balakedara'];


    $nabikarana = "UPDATE shonu_kaichila
                   SET motta = ROUND(motta + '".$motta."', 2)
                   WHERE balakedara = '".$shonuid."'";
    
    if (!$conn->query($nabikarana)) {
        logError("Database update error: " . mysqli_error($conn));
        echo json_encode([
            "message" => "fail(update error)",
            "status" => false,
        ]);
        exit;
    }


    $sql2 = mysqli_query($conn, "UPDATE thevani SET sthiti = '1' WHERE dharavahi = '".$mchOrderNo."'");

    if (!$sql2) {
        logError("Database update error: " . mysqli_error($conn));
        echo json_encode([
            "message" => "fail(update error)",
            "status" => false,
        ]);
        exit;
    }
} else {
    echo "ok"; 
exit;
}

echo "ok"; 
exit;
?>
