<?php

use PHPUnit\Framework\TestCase;

/**
 * Exercise native setcookie() under PHP's real HTTP server SAPI. CLI PHPUnit
 * deliberately does not retain HTTP headers, so pure CLI header assertions
 * would silently inspect an empty headers_list().
 */
class NetworkCookieHeaderTest extends TestCase
{
    private function startLocalServer()
    {
        if (!function_exists('proc_open') ||
            !function_exists('stream_socket_server') ||
            !function_exists('stream_socket_client')
        ) {
            self::fail('The local HTTP cookie regression test requires process and socket support.');
        }

        $errno = 0;
        $error = '';
        $socket = @stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
        if (!is_resource($socket)) {
            self::fail('Cannot reserve a local HTTP test port: ' . $error);
        }

        $name = stream_socket_get_name($socket, false);
        $port = (int)substr(strrchr($name, ':'), 1);
        fclose($socket);

        $log = tempnam(sys_get_temp_dir(), 'tnet-cookie-');
        if ($log === false) {
            self::fail('Cannot allocate a local HTTP test log.');
        }

        // exec replaces the shell with PHP so proc_terminate stops the server.
        $command = 'exec ' . escapeshellarg(PHP_BINARY)
            . ' -S ' . escapeshellarg('127.0.0.1:' . $port)
            . ' -t ' . escapeshellarg(__DIR__ . '/fixtures');
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['file', $log, 'a'],
            2 => ['file', $log, 'a'],
        ];
        $pipes = [];
        $process = proc_open($command, $descriptors, $pipes);
        if (!is_resource($process)) {
            @unlink($log);
            self::fail('Cannot start the local PHP cookie fixture server.');
        }
        fclose($pipes[0]);

        $ready = false;
        for ($attempt = 0; $attempt < 60; $attempt++) {
            $connection = @stream_socket_client(
                'tcp://127.0.0.1:' . $port,
                $errno,
                $error,
                0.1
            );
            if (is_resource($connection)) {
                fclose($connection);
                $ready = true;
                break;
            }
            usleep(25000);
        }

        if (!$ready) {
            proc_terminate($process);
            proc_close($process);
            $logs = file_get_contents($log);
            @unlink($log);
            self::fail('The local HTTP test server did not become ready: ' . $logs);
        }

        return [$process, $port, $log];
    }

    private function requestCookieHeaders($port, $scenario)
    {
        $url = 'http://127.0.0.1:' . $port . '/cookie-response.php?scenario='
            . rawurlencode($scenario);
        $context = stream_context_create([
            'http' => ['timeout' => 3, 'ignore_errors' => true],
        ]);
        $response = @file_get_contents($url, false, $context);
        self::assertSame('OK', $response, 'Unexpected fixture response: ' . $scenario);

        $matches = [];
        foreach ($http_response_header as $header) {
            if (stripos($header, 'Set-Cookie: ') === 0) {
                $matches[] = substr($header, strlen('Set-Cookie: '));
            }
        }
        return $matches;
    }

    private function requestCookieHeader($port, $scenario)
    {
        $matches = $this->requestCookieHeaders($port, $scenario);
        self::assertCount(1, $matches, 'Missing or duplicated Set-Cookie header: ' . $scenario);

        return $matches[0];
    }

    private function assertCookieAttribute($header, $attribute, $expected)
    {
        $actual = preg_match(
            '/(?:^|;\s*)' . preg_quote($attribute, '/') . '(?:;|$)/i',
            $header
        ) === 1;
        self::assertSame($expected, $actual, $attribute . ' in ' . $header);
    }

    public function testActualSetCookieHeadersIncludeExpectedSecurityAndAttributes()
    {
        list($process, $port, $log) = $this->startLocalServer();
        try {
            $header = $this->requestCookieHeader($port, 'configured');
            $nameAndValue = explode('=', substr($header, 0, strpos($header, ';')), 2);
            self::assertSame('pre_access', $nameAndValue[0]);
            self::assertSame('hello world', urldecode($nameAndValue[1]));
            self::assertTrue(stripos($header, 'path=/configured') !== false, $header);
            self::assertTrue(stripos($header, 'domain=second.example.test') !== false, $header);
            $expires = [];
            self::assertSame(1, preg_match('/(?:^|;\\s*)expires=([^;]+)/i', $header, $expires));
            self::assertSame(2000000000, strtotime($expires[1]));
            $this->assertCookieAttribute($header, 'Secure', true);

            $header = $this->requestCookieHeader($port, 'path_preserved');
            self::assertTrue(stripos($header, 'path=/keep') !== false, $header);

            foreach (['auto_https_on', 'auto_https_1', 'auto_https_true'] as $scenario) {
                $header = $this->requestCookieHeader($port, $scenario);
                $this->assertCookieAttribute($header, 'Secure', true);
            }

            foreach (['trusted_forwarded', 'trusted_ipv6_forwarded'] as $scenario) {
                $header = $this->requestCookieHeader($port, $scenario);
                $this->assertCookieAttribute($header, 'Secure', true);
            }

            foreach (['untrusted_forwarded', 'explicit_insecure'] as $scenario) {
                $header = $this->requestCookieHeader($port, $scenario);
                $this->assertCookieAttribute($header, 'Secure', false);
            }

            foreach (['missing_host', 'localhost', 'ipv4', 'ipv6'] as $scenario) {
                $header = $this->requestCookieHeader($port, $scenario);
                self::assertFalse(
                    stripos($header, 'domain=') !== false,
                    'Unexpected Domain attribute for ' . $scenario . ': ' . $header
                );
            }

            foreach ([
                'public_suffix_co_uk',
                'public_suffix_github_io',
                'malformed_host',
            ] as $scenario) {
                $header = $this->requestCookieHeader($port, $scenario);
                self::assertFalse(
                    stripos($header, 'domain=') !== false,
                    'Public suffix or malformed Host must not set Domain: ' . $header
                );
            }

            $header = $this->requestCookieHeader($port, 'private_domain');
            self::assertTrue(
                stripos($header, 'domain=.alice.github.io') !== false,
                $header
            );

            $headers = $this->requestCookieHeaders($port, 'changing_request');
            self::assertCount(3, $headers);
            self::assertTrue(strpos($headers[0], 'first=') === 0);
            self::assertTrue(stripos($headers[0], 'domain=.first.example.test') !== false);
            $this->assertCookieAttribute($headers[0], 'Secure', true);
            self::assertTrue(strpos($headers[1], 'second=') === 0);
            self::assertFalse(stripos($headers[1], 'domain=') !== false);
            $this->assertCookieAttribute($headers[1], 'Secure', false);
            self::assertTrue(strpos($headers[2], 'third=') === 0);
            self::assertTrue(stripos($headers[2], 'domain=.second.example.test') !== false);
            $this->assertCookieAttribute($headers[2], 'Secure', true);

            $header = $this->requestCookieHeader($port, 'default_expiry');
            self::assertTrue(stripos($header, 'expires=') !== false, $header);
        } finally {
            proc_terminate($process);
            proc_close($process);
            @unlink($log);
        }
    }
}
