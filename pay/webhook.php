<?php
header("Content-Type: application/json");
include("../service/samparka.php");

    $input = file_get_contents("php://input");
    $data = json_decode($input, true);
    
    // Check if valid data received
    if (!$data || !isset($data['order_id']) || !isset($data['status']) || $data['status'] !== 'success') {
        echo json_encode(["status" => false, "message" => "Invalid webhook data"]);
        exit;
    }
    
    $order_id = $data['order_id'];
    $amount = $data['amount'];
    $uidQuery = $conn->prepare("SELECT balakedara FROM `thevani` WHERE dharavahi = ?");
    $uidQuery->bind_param("s", $order_id);
    $uidQuery->execute();
    $uidQuery->bind_result($uid);
    $uidQuery->fetch();
    $uidQuery->close();
    
    if (!$uid) {
        echo json_encode(["status" => false, "message" => "Order not found"]);
        exit;
    }
    
    // Update payment status in `thevani`
    $updatePayment = $conn->prepare("UPDATE `thevani` SET sthiti = '1' WHERE dharavahi = ?");
    $updatePayment->bind_param("s", $order_id);
    $updatePayment->execute();
    $updatePayment->close();
    
    // Add money to user's balance
    $updateBalance = $conn->prepare("UPDATE `shonu_kaichila` SET motta = motta + ? WHERE balakedara = ?");
    $updateBalance->bind_param("ds", $amount, $uid);
    $updateBalance->execute();
    $updateBalance->close();
    
    echo json_encode(["status" => true, "message" => "Payment successful, balance updated"]);
?>
