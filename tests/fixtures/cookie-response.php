<?php

/**
 * Local-only HTTP fixture. Run by NetworkCookieHeaderTest via PHP's built-in
 * server so the native PHP setcookie() output can be inspected as HTTP.
 */
require __DIR__ . '/../../vendor/autoload.php';

use TorneLIB\Module\Network\Cookie;

$scenario = isset($_GET['scenario']) ? (string)$_GET['scenario'] : '';

$_SERVER['HTTPS'] = 'off';
$_SERVER['REMOTE_ADDR'] = '198.51.100.10';
$_SERVER['HTTP_HOST'] = 'www.example.test:8443';
unset($_SERVER['HTTP_X_FORWARDED_PROTO'], $_SERVER['HTTP_X_FORWARDED_FOR']);

$cookie = new Cookie();
$name = 'token';
$value = 'value';
$expires = 2000000000;

switch ($scenario) {
    case 'configured':
        $cookie->setCookieParameters('/configured', 'pre_', 'first.example.test', true);
        $cookie->setCookieParameters('/configured', null, 'second.example.test', null);
        $name = 'access';
        $value = 'hello world';
        break;

    case 'path_preserved':
        $cookie->setCookieDefaultPath('/keep');
        break;

    case 'auto_https_on':
        $_SERVER['HTTPS'] = 'on';
        break;

    case 'auto_https_1':
        $_SERVER['HTTPS'] = '1';
        break;

    case 'auto_https_true':
        $_SERVER['HTTPS'] = 'true';
        break;

    case 'untrusted_forwarded':
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
        $cookie->setCookieTrustedProxies(['192.0.2.0/24']);
        break;

    case 'trusted_forwarded':
        $_SERVER['REMOTE_ADDR'] = '192.0.2.10';
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
        $cookie->setCookieTrustedProxies(['192.0.2.0/24']);
        break;

    case 'trusted_ipv6_forwarded':
        $_SERVER['REMOTE_ADDR'] = '2001:db8::10';
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
        $cookie->setCookieTrustedProxies(['2001:db8::/32']);
        break;

    case 'explicit_insecure':
        $_SERVER['HTTPS'] = 'on';
        $cookie->setCookieParameters('/private', null, 'example.test', false);
        break;

    case 'missing_host':
        unset($_SERVER['HTTP_HOST']);
        break;

    case 'localhost':
        $_SERVER['HTTP_HOST'] = 'localhost';
        break;

    case 'ipv4':
        $_SERVER['HTTP_HOST'] = '127.0.0.1:8443';
        break;

    case 'ipv6':
        $_SERVER['HTTP_HOST'] = '[::1]:8443';
        break;

    case 'default_expiry':
        $expires = '';
        break;

    default:
        http_response_code(400);
        echo 'Invalid fixture case';
        exit;
}

header('Content-Type: text/plain');
if (!$cookie->setCookie($name, $value, $expires)) {
    http_response_code(500);
    echo 'Cookie could not be emitted';
    exit;
}

echo 'OK';
