<?php
class PHPGangsta_GoogleAuthenticator {
    protected $_codeLength = 6;

    public function createSecret($secretLength = 16) {
        $validChars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        for ($i = 0; $i < $secretLength; $i++) {
            $secret .= $validChars[rand(0, strlen($validChars) - 1)];
        }
        return $secret;
    }

    public function getQRCodeGoogleUrl($name, $secret, $title = null) {
        $urlencoded = urlencode("otpauth://totp/{$name}?secret={$secret}");
        return "https://chart.googleapis.com/chart?chs=200x200&chld=M|0&cht=qr&chl={$urlencoded}";
    }

    public function verifyCode($secret, $code, $discrepancy = 1) {
        $currentTimeSlice = floor(time() / 30);
        for ($i = -$discrepancy; $i <= $discrepancy; $i++) {
            $calculatedCode = $this->getCode($secret, $currentTimeSlice + $i);
            if ($calculatedCode == $code) {
                return true;
            }
        }
        return false;
    }

    public function getCode($secret, $timeSlice = null) {
        if ($timeSlice === null) {
            $timeSlice = floor(time() / 30);
        }
        $secretkey = $this->_base32Decode($secret);
        $time = chr(0).chr(0).chr(0).chr(0).pack('N*', $timeSlice);
        $hmac = hash_hmac('SHA1', $time, $secretkey, true);
        $offset = ord(substr($hmac, -1)) & 0x0F;
        $hashpart = substr($hmac, $offset, 4);
        $value = unpack('N', $hashpart);
        $value = $value[1] & 0x7FFFFFFF;
        $modulo = pow(10, $this->_codeLength);
        return str_pad($value % $modulo, $this->_codeLength, '0', STR_PAD_LEFT);
    }

    protected function _base32Decode($secret) {
        if (empty($secret)) return '';
        $base32chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $base32charsFlipped = array_flip(str_split($base32chars));
        $paddingCharCount = substr_count($secret, '=');
        $allowedValues = array(6, 4, 3, 1, 0);
        if (!in_array($paddingCharCount, $allowedValues)) return false;
        $secret = str_replace('=', '', $secret);
        $binaryString = '';
        for ($i = 0; $i < strlen($secret); $i += 8) {
            $x = '';
            for ($j = 0; $j < 8; $j++) {
                if (isset($secret[$i + $j])) {
                    $x .= str_pad(base_convert($base32charsFlipped[$secret[$i + $j]], 10, 2), 5, '0', STR_PAD_LEFT);
                }
            }
            $eightBits = str_split($x, 8);
            foreach ($eightBits as $byte) {
                if (strlen($byte) === 8) {
                    $binaryString .= chr(base_convert($byte, 2, 10));
                }
            }
        }
        return $binaryString;
    }
}
?>
