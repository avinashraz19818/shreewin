<?php
function generate_jwt($headers, $payload, $secret = 'bdgshonuuncensored') {
    $headers_encoded = base64url_encode(json_encode($headers));
    $payload_encoded = base64url_encode(json_encode($payload));
    $signature = hash_hmac('SHA256', "$headers_encoded.$payload_encoded", $secret, true);
    $signature_encoded = base64url_encode($signature);
    return "$headers_encoded.$payload_encoded.$signature_encoded";
}

function is_jwt_valid($jwt, $secret = 'bdgshonuuncensored') {
    $res = ['status' => 'Failed', 'payload' => ''];
    if (!is_string($jwt) || $jwt === '') {
        return json_encode($res);
    }

    $tokenParts = explode('.', $jwt);
    if (count($tokenParts) !== 3 || $tokenParts[0] === '' || $tokenParts[1] === '' || $tokenParts[2] === '') {
        return json_encode($res);
    }

    [$headerPart, $payloadPart, $signatureProvided] = $tokenParts;
    $header = base64url_decode($headerPart);
    $payload = base64url_decode($payloadPart);
    if ($header === false || $payload === false) {
        return json_encode($res);
    }

    $signature = hash_hmac('SHA256', $headerPart . '.' . $payloadPart, $secret, true);
    $signatureEncoded = base64url_encode($signature);
    if (!hash_equals($signatureEncoded, $signatureProvided)) {
        return json_encode($res);
    }

    $decodedPayload = json_decode($payload, true);
    if (!is_array($decodedPayload)) {
        return json_encode($res);
    }

    $res['status'] = 'Success';
    $res['payload'] = $decodedPayload;
    return json_encode($res);
}

function base64url_encode($str) {
    return rtrim(strtr(base64_encode((string)$str), '+/', '-_'), '=');
}

function base64url_decode($str) {
    if (!is_string($str)) {
        return false;
    }
    $remainder = strlen($str) % 4;
    if ($remainder) {
        $str .= str_repeat('=', 4 - $remainder);
    }
    return base64_decode(strtr($str, '-_', '+/'), true);
}
?>
