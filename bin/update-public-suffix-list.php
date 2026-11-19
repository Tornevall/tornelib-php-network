<?php

/**
 * Download and validate the official Public Suffix List, then store a
 * deterministic ASCII/Punycode snapshot for use without runtime network I/O.
 *
 * Requires PHP with ext-intl and allow_url_fopen for this maintenance command.
 * Production domain lookups do not require the updater or external DNS.
 */

if (!function_exists('idn_to_ascii') ||
    !defined('INTL_IDNA_VARIANT_UTS46') ||
    !defined('IDNA_NONTRANSITIONAL_TO_ASCII')
) {
    fwrite(STDERR, "Updating the Public Suffix List requires ext-intl with UTS46 support.\n");
    exit(1);
}

$url = 'https://publicsuffix.org/list/public_suffix_list.dat';
$context = stream_context_create([
    'http' => ['timeout' => 30, 'follow_location' => 0],
    'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
]);
$data = @file_get_contents($url, false, $context);
if (!is_string($data) || strlen($data) < 100000 ||
    strpos($data, '// ===BEGIN ICANN DOMAINS===') === false ||
    strpos($data, '// ===END ICANN DOMAINS===') === false ||
    strpos($data, '// ===BEGIN PRIVATE DOMAINS===') === false ||
    strpos($data, '// ===END PRIVATE DOMAINS===') === false
) {
    fwrite(STDERR, "Could not retrieve a complete Public Suffix List from its canonical endpoint.\n");
    exit(1);
}

$output = [
    '// This Source Code Form is subject to the terms of the Mozilla Public',
    '// License, v. 2.0. If a copy of the MPL was not distributed with this',
    '// file, You can obtain one at https://mozilla.org/MPL/2.0/.',
    '//',
    '// GENERATED ASCII (Punycode) SNAPSHOT of the Public Suffix List (ICANN + PRIVATE).',
    '// Official canonical update endpoint: ' . $url,
    '// Never edit this generated file. Use bin/update-public-suffix-list.php.',
    '',
];
$section = '';
$count = 0;

foreach (preg_split('/\r\n|\n|\r/', $data) as $line) {
    $line = trim($line);

    if ($line === '// ===BEGIN ICANN DOMAINS===') {
        $section = 'ICANN';
        $output[] = $line;
        continue;
    }
    if ($line === '// ===END ICANN DOMAINS===') {
        $section = '';
        $output[] = $line;
        continue;
    }
    if ($line === '// ===BEGIN PRIVATE DOMAINS===') {
        $section = 'PRIVATE';
        $output[] = $line;
        continue;
    }
    if ($line === '// ===END PRIVATE DOMAINS===') {
        $section = '';
        $output[] = $line;
        continue;
    }

    if ($section === '' || $line === '' || strpos($line, '//') === 0) {
        continue;
    }

    $marker = '';
    if ($line[0] === '!') {
        $marker = '!';
    } elseif (strpos($line, '*.') === 0) {
        $marker = '*.';
    }

    $domain = substr($line, strlen($marker));
    $ascii = idn_to_ascii(
        $domain,
        IDNA_NONTRANSITIONAL_TO_ASCII,
        INTL_IDNA_VARIANT_UTS46
    );

    if (!is_string($ascii) || $ascii === '' ||
        preg_match('/^[a-z0-9.-]+$/i', $ascii) !== 1
    ) {
        fwrite(STDERR, sprintf("Cannot normalize PSL rule: %s\n", $line));
        exit(1);
    }

    $output[] = $marker . strtolower($ascii);
    $count++;
}

if ($count < 10000) {
    fwrite(STDERR, sprintf("Refusing to save an incomplete PSL (%d rules).\n", $count));
    exit(1);
}

$destination = __DIR__ . '/../resources/public_suffix_list.dat';
$temporary = $destination . '.tmp';
$contents = implode("\n", $output) . "\n";

if (file_put_contents($temporary, $contents, LOCK_EX) !== strlen($contents) ||
    !rename($temporary, $destination)
) {
    @unlink($temporary);
    fwrite(STDERR, "Unable to atomically update the local Public Suffix List.\n");
    exit(1);
}

fwrite(STDOUT, sprintf("Updated PSL with %d ICANN and PRIVATE rules.\n", $count));
