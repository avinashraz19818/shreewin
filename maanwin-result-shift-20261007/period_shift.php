<?php
/**
 * ============================================================================
 *  period_shift.php — issue number ko N period piche/age karo
 * ============================================================================
 *  SETTING:
 *      AR_PERIOD_SHIFT = -1   -> period AUR RESULT dono 1 piche  (DEFAULT)
 *      AR_PERIOD_SHIFT =  0   -> shift band (upstream jaisa)
 *      AR_PERIOD_SHIFT = -2   -> 2 period piche
 * ============================================================================
 */

if (!defined('AR_PERIOD_SHIFT')) {
    define('AR_PERIOD_SHIFT', -1);
}

/** bade integer (17 digit) me chhota int add/sub karo — bcmath ki zarurat nahi */
function ar_big_add_small(string $s, int $n): string
{
    if ($n === 0 || $s === '' || !ctype_digit($s)) return $s;
    $d = str_split($s);
    $i = count($d) - 1;

    if ($n > 0) {
        $carry = $n;
        while ($i >= 0 && $carry > 0) {
            $v = (int)$d[$i] + $carry;
            $d[$i] = (string)($v % 10);
            $carry = intdiv($v, 10);
            $i--;
        }
        while ($carry > 0) { array_unshift($d, (string)($carry % 10)); $carry = intdiv($carry, 10); }
    } else {
        $borrow = -$n;
        while ($i >= 0 && $borrow > 0) {
            $v = (int)$d[$i] - $borrow;
            if ($v < 0) { $v += 10; $borrow = 1; } else { $borrow = 0; }
            $d[$i] = (string)$v;
            $i--;
        }
    }
    $out = ltrim(implode('', $d), '0');
    return $out === '' ? '0' : $out;
}

/** issue-number jaisi keys ko recursively shift karo */
function ar_shift_numbers($node, int $n)
{
    if (!is_array($node) || $n === 0) return $node;
    foreach ($node as $k => $v) {
        $key = (string)$k;
        if (is_string($v) && preg_match('/^(issueNumber|issueNo|issueId|issue|period|periodNo)$/i', $key)) {
            $node[$k] = ar_big_add_small(trim($v), $n);
        } elseif (is_array($v)) {
            $node[$k] = ar_shift_numbers($v, $n);
        }
    }
    return $node;
}

/** poora JSON body shift karo */
function ar_shift_json_body(string $body, int $n): string
{
    if ($n === 0 || trim($body) === '') return $body;
    $data = json_decode($body, true);
    if (!is_array($data)) return $body;      // JSON nahi hai to waise hi bhej do
    $data = ar_shift_numbers($data, $n);
    $out  = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    return $out === false ? $body : $out;
}
