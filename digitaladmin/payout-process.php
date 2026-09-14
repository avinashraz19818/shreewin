<?php
session_start();
if (!isset($_SESSION['unohs'])) {
    header("location: api/login.php?msg=unauthorized");
    exit;
}

// Load payout API configuration
require_once('payoutapi_config.php');
include("api/conn.php");
require_once __DIR__ . '/api/manual-withdraw-helper.php';

// Prefer the active SQL-managed gateway; retain the legacy constants only as
// a compatibility fallback until the admin saves a gateway configuration.
$payoutUrl = PAY_URL; $payoutMerchant = MERCHANT_ID; $payoutSecret = SECRET_KEY;
$payoutNotify = NOTIFY_URL; $payoutCurrency = CURRENCY_CODE;
try {
    $gatewayResult = $conn->query('SELECT api_url,merchant_id,secret_key,notify_url,currency_code FROM auto_payout_gateways WHERE is_active=1 ORDER BY id DESC LIMIT 1');
    $gateway = $gatewayResult ? $gatewayResult->fetch_assoc() : null;
    if ($gateway) {
        $payoutUrl=rtrim((string)$gateway['api_url'],'/'); $payoutMerchant=(string)$gateway['merchant_id'];
        $payoutSecret=(string)$gateway['secret_key']; $payoutNotify=(string)$gateway['notify_url'];
        $payoutCurrency=(string)$gateway['currency_code'];
    }
} catch (Throwable $e) { error_log('[payout config] '.$e->getMessage()); }

// Function to generate signature (Section 8 of docs)
function generateSignature($params, $secretKey) {
    ksort($params);
    $string = '';
    foreach ($params as $key => $value) {
        if ($key !== 'sign' && $value !== '' && $value !== null && $value !== false) {
            $string .= "$key=" . urlencode($value) . "&";
        }
    }
    $string = rtrim($string, '&');
    $string .= "&key=$secretKey";
    return strtoupper(md5($string));
}

// Handle payout request (only for Accept action)
if (isset($_POST['id']) && isset($_POST['type']) && $_POST['type'] === 'accept') {
    $shonuId = mysqli_real_escape_string($conn, $_POST['id']);
    $remark = mysqli_real_escape_string($conn, $_POST['remark'] ?? '');
    $apiChoice = $_POST['apiChoice'] ?? 'rupeerush';

    if (!$shonuId) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 0, 'message' => 'Invalid withdrawal ID']);
        exit;
    }

    // Fetch withdrawal details
    $Query = mysqli_query($conn, "SELECT shonu_subjects.mobile, shonu_subjects.email, shonu_subjects.owncode, 
        khate.phalanubhavi, khate.kod, khate.khatehesaru, khate.khatesankhye, 
        hintegedukolli.shonu, hintegedukolli.motta, hintegedukolli.khateshonu, 
        hintegedukolli.sthiti, hintegedukolli.dinankavannuracisi 
        FROM hintegedukolli 
        INNER JOIN shonu_subjects ON shonu_subjects.id = hintegedukolli.balakedara 
        INNER JOIN khate ON khate.shonu = hintegedukolli.khateshonu 
        WHERE hintegedukolli.shonu = '$shonuId'");
    
    if ($Result = mysqli_fetch_array($Query)) {
        $currentState = (int)$Result['sthiti'];
        if (!in_array($currentState, [0, 3], true)) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 0, 'message' => 'Withdrawal request is already processed']);
            exit;
        }
        // Prepare payout API parameters (Section 5)
        $params = [
            'merNo' => $payoutMerchant,
            'currencyCode' => $payoutCurrency,
            'randomNo' => substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 14),
            'outTradeNo' => 'M' . time() . rand(1000, 9999),
            'totalAmount' => number_format($Result['motta'], 2, '.', ''),
            'accountName' => $Result['phalanubhavi'],
            'accountNumber' => $Result['khatesankhye'],
            'bankCode' => $Result['kod'], // Assuming IFSC maps to bankCode
            'notifyUrl' => $payoutNotify
        ];

        // Store outTradeNo in DB
        $outTradeNo = $params['outTradeNo'];
        $storeTradeNo = mysqli_query($conn, "UPDATE hintegedukolli 
            SET out_trade_no = '$outTradeNo' 
            WHERE shonu = '$shonuId'");
        if (!$storeTradeNo) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 0, 'message' => 'Failed to store transaction ID']);
            exit;
        }

        // Generate signature
        $params['sign'] = generateSignature($params, $payoutSecret);

        // Make API request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $payoutUrl . '/payout/create');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json; charset=UTF-8',
            'Accept: application/json'
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        // Parse response
        $responseData = json_decode($response, true);
        $error = $curlError ?: ($httpCode !== 200 ? "HTTP $httpCode" : '');

        if (!$error && isset($responseData['resultCode'])) {
            if ($responseData['resultCode'] === '0000') {
                // Success
                $payOrderNo = $responseData['payOrderNo'] ?? '';
                [$ok, $actionMessage] = admin_manual_withdraw_action($conn, (int)$shonuId, 'accept', $remark);
                if ($ok) {
                    $payOrderNoEsc = mysqli_real_escape_string($conn, (string)$payOrderNo);
                    mysqli_query($conn, "UPDATE hintegedukolli SET pay_order_no = '$payOrderNoEsc', out_trade_no = '$outTradeNo', updated_at = NOW() WHERE shonu = '$shonuId'");
                    header('Content-Type: application/json');
                    echo json_encode(['status' => 1, 'message' => 'Payout processed successfully via ' . strtoupper($apiChoice)]);
                    exit;
                }
                header('Content-Type: application/json');
                echo json_encode(['status' => 0, 'message' => $actionMessage]);
                exit;
            } else {
                // API Error
                $errorMsg = $responseData['stateInfo'] ?? 'API error code: ' . $responseData['resultCode'];
                [$rejected, $rejectMessage] = admin_manual_withdraw_action($conn, (int)$shonuId, 'reject', (string)$errorMsg);
                header('Content-Type: application/json');
                echo json_encode(['status' => $rejected ? 2 : 0, 'message' => $rejected ? $errorMsg : $rejectMessage]);
                exit;
            }
        } else {
            // cURL or HTTP Error
            $errorMsg = $error ?: ($responseData['stateInfo'] ?? 'No response from API');
            [$rejected, $rejectMessage] = admin_manual_withdraw_action($conn, (int)$shonuId, 'reject', (string)$errorMsg);
            header('Content-Type: application/json');
            echo json_encode(['status' => $rejected ? 2 : 0, 'message' => $rejected ? $errorMsg : $rejectMessage]);
            exit;
        }
    } else {
        header('Content-Type: application/json');
        echo json_encode(['status' => 0, 'message' => 'Invalid withdrawal request']);
        exit;
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['status' => 0, 'message' => 'Invalid request - Only Accept action supported']);
    exit;
}
?>
