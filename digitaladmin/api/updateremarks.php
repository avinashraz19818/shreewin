<?php
session_start();
include("conn.php");

if (empty($_SESSION['unohs'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized access"]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)$_POST['id'];
    $formid = (int)$_POST['formid'];
    $remarks = mysqli_real_escape_string($conn, trim($_POST['remarks']));

    // Common validation
    if($id < 1 || $formid < 1 || empty($remarks)) {
        echo json_encode(["status" => "error", "message" => "Invalid input parameters"]);
        exit;
    }

    $response = ["status" => "error", "message" => "Unknown error"];
    
    try {
        switch($formid) {
            // Case 1: Approve Bank Account Deletion
            case 89:
                mysqli_begin_transaction($conn);
                
                // Update request status
                mysqli_query($conn, "UPDATE your_table SET remarks='$remarks', status=4 WHERE id=$id");
                
                // Delete from khate table
                mysqli_query($conn, "DELETE FROM khate WHERE byabaharkarta=(
                    SELECT userid FROM your_table WHERE id=$id
                ) AND khatehesaru != 'TRC'");
                
                mysqli_commit($conn);
                $response = ["status" => "success", "message" => "Bank account deleted successfully"];
                break;

            // Case 2: Reject Bank Account Modification Request
            case 883:
                mysqli_query($conn, "UPDATE your_table SET remarks='$remarks', status=3 WHERE id=$id");
                $response = ["status" => "success", "message" => "Request rejected successfully"];
                break;

            // Case 3: Other Rejection Cases
            case 884:
            case 14:
            case 25:
            case 16:
            case 15:
            case 75:
                mysqli_query($conn, "UPDATE your_table SET remarks='$remarks', status=4 WHERE id=$id");
                $response = ["status" => "success", "message" => "Request processed successfully"];
                break;

            // Case 4: USDT Verification Approval
            case 890:
                mysqli_query($conn, "UPDATE usdt_verification SET remarks='$remarks', status=1 WHERE id=$id");
                $response = ["status" => "success", "message" => "USDT verification approved"];
                break;

            default:
                $response = ["status" => "error", "message" => "Invalid form ID"];
                break;
        }
    } catch(Exception $e) {
        mysqli_rollback($conn);
        $response = ["status" => "error", "message" => "Operation failed: ".$e->getMessage()];
    }

    echo json_encode($response);
    
} else {
    echo json_encode(["status" => "error", "message" => "Invalid request method"]);
}

mysqli_close($conn);
?>