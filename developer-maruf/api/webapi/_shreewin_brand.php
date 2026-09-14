<?php
/**
 * Public-facing Shree Win branding helpers used by site-message endpoints.
 * Internal database keys and admin authentication are intentionally untouched.
 */

if (!defined('SHREEWIN_BRAND_IMAGE_URL')) {
    define(
        'SHREEWIN_BRAND_IMAGE_URL',
        'https://ossimg.shreewinpay.com/shreewin/other/h5setting_20260824191731heop.png'
    );
}

if (!function_exists('shreewin_public_origin')) {
    function shreewin_public_origin()
    {
        $fallback = 'https://shreewin.club9.eu.cc';
        $origin = isset($_SERVER['HTTP_ORIGIN']) ? trim((string) $_SERVER['HTTP_ORIGIN']) : '';
        if ($origin === '') {
            return $fallback;
        }

        $parts = parse_url($origin);
        $scheme = isset($parts['scheme']) ? strtolower((string) $parts['scheme']) : '';
        $host = isset($parts['host']) ? strtolower((string) $parts['host']) : '';
        $allowedHosts = array(
            'shreewin.club9.eu.cc',
            'www.shreewin.club9.eu.cc',
            'shreewin1.com',
            'www.shreewin1.com',
        );

        if (!in_array($scheme, array('http', 'https'), true) || !in_array($host, $allowedHosts, true)) {
            return $fallback;
        }

        return $scheme . '://' . $host;
    }
}

if (!function_exists('shreewin_brand_content')) {
    function shreewin_brand_content($value)
    {
        if (!is_string($value) || $value === '') {
            return $value;
        }

        // Remove legacy Daman-hosted artwork from messages and use the current
        // Shree Win project artwork instead.
        $value = preg_replace(
            '~https?://ossimg\.envyenvelope\.com/daman/[^\"\'<>[:space:]]+~i',
            SHREEWIN_BRAND_IMAGE_URL,
            $value
        );

        // Keep official links on this Shree Win deployment.
        $value = preg_replace(
            '~https?://(?:www\.)?(?:damanvipgame\.com|damanworld\.com)(?:/[^\"\'<>[:space:]]*)?~i',
            shreewin_public_origin() . '/',
            $value
        );

        return str_ireplace(
            array(
                'Official DamanGames Link',
                'DamanGames platform',
                'DamanGames website',
                'DamanGames Telegram',
                'DamanGames',
                'Daman Games',
                'Daman Game',
                'Daman Support',
                'Daman',
            ),
            array(
                'Official Shree Win Link',
                'Shree Win platform',
                'Shree Win website',
                'Shree Win Telegram',
                'Shree Win',
                'Shree Win',
                'Shree Win',
                'Shree Win Support',
                'Shree Win',
            ),
            $value
        );
    }
}

