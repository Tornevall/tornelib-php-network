<?php

namespace TorneLIB\Module\Network;

use Exception;
use TorneLIB\Exception\Constants;
use TorneLIB\Module\Exception\DomainException;

/**
 * Internet domain, URL extraction and redirect helpers.
 *
 * @version 6.1.5
 */
class Domain
{
    /**
     * Parsed local PSL snapshot shared across Domain instances for this process.
     *
     * @var array|null
     */
    private static $publicSuffixRules;

    /**
     * Resolve DNS entries; override in deterministic tests or custom deployments.
     *
     * @param string $hostname
     * @return array|false
     */
    protected function resolveDnsRecords(string $hostname)
    {
        return @dns_get_record($hostname, DNS_ANY);
    }

    private function getUrlDomainValidatedHost(string $requestedUrl, $parsed)
    {
        if (!is_array($parsed) ||
            empty($parsed['host']) ||
            filter_var($requestedUrl, FILTER_VALIDATE_URL) === false ||
            filter_var($parsed['host'], FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) === false
        ) {
            throw new DomainException(
                sprintf('Host validation for "%s" in %s failed.', $requestedUrl, __FUNCTION__),
                Constants::LIB_NETCURL_DOMAIN_OR_HOST_VALIDATION_FAILURE
            );
        }

        $records = $this->resolveDnsRecords($parsed['host']);
        if (!is_array($records) || count($records) === 0) {
            throw new DomainException(
                sprintf('Host validation for "%s" in %s failed.', $requestedUrl, __FUNCTION__),
                Constants::LIB_NETCURL_DOMAIN_OR_HOST_VALIDATION_FAILURE
            );
        }
    }

    /**
     * Extract unique quoted absolute URLs matching the requested schemes.
     * Legacy 'http' matches both http and https.
     *
     * @param string $html
     * @param array $protocols
     * @return array
     */
    private function getUrlsByProtocol($html, $protocols = ['http'])
    {
        $allowedProtocols = array_map('strtolower', $protocols);
        if (in_array('http', $allowedProtocols, true) &&
            !in_array('https', $allowedProtocols, true)
        ) {
            $allowedProtocols[] = 'https';
        }

        $urls = [];
        preg_match_all('~(["\x27])([a-z][a-z0-9+.-]*:[^"\x27]*?)\1~i', $html, $matches);
        foreach ($matches[2] as $url) {
            $scheme = parse_url($url, PHP_URL_SCHEME);
            if (is_string($scheme) &&
                in_array(strtolower($scheme), $allowedProtocols, true) &&
                !in_array($url, $urls, true)
            ) {
                $urls[] = $url;
            }
        }

        return $urls;
    }

    /**
     * Apply offset and limit independently, including for the default offset.
     *
     * @param array $urls
     * @param int $offset
     * @param int $limit
     * @return array
     */
    private function getUrlsByOffset($urls, $offset, $limit)
    {
        $start = $offset < 0 ? 0 : $offset;
        if ($start >= count($urls) || $limit === 0) {
            return [];
        }

        return array_slice($urls, $start, $limit < 0 ? null : $limit);
    }

    /**
     * Returns minimal parsed information about a hostname or URL.
     *
     * @param string $requestedUrlHost
     * @param bool $validateHost
     * @return array
     * @throws DomainException
     */
    public function getUrlDomain($requestedUrlHost = '', $validateHost = false): array
    {
        if (strpos($requestedUrlHost, '://') === false) {
            $requestedUrlHost = 'http://' . $requestedUrlHost;
        }

        $parsed = parse_url($requestedUrlHost);
        if ($validateHost) {
            $this->getUrlDomainValidatedHost($requestedUrlHost, $parsed);
        }

        if (!is_array($parsed)) {
            return [null, null, null];
        }

        return [
            isset($parsed['host']) ? $parsed['host'] : null,
            isset($parsed['scheme']) ? $parsed['scheme'] : null,
            isset($parsed['path']) ? $parsed['path'] : null,
        ];
    }

    /**
     * @param string $stringWithUrls
     * @param int $offset
     * @param int $urlLimit
     * @param array $protocols
     * @return array
     */
    public function getUrlsFromHtml(
        string $stringWithUrls,
        int $offset = -1,
        int $urlLimit = -1,
        array $protocols = ['http']
    ): array {
        return $this->getUrlsByOffset(
            $this->getUrlsByProtocol($stringWithUrls, $protocols),
            $offset,
            $urlLimit
        );
    }

    /**
     * Load the complete local PSL snapshot, including ICANN and PRIVATE rules.
     * Absence or damage is an error; silently guessing is unsafe for cookies.
     *
     * @return array
     * @throws DomainException
     */
    private function getPublicSuffixRules(): array
    {
        if (self::$publicSuffixRules !== null) {
            return self::$publicSuffixRules;
        }

        $file = __DIR__ . '/../../../resources/public_suffix_list.dat';
        if (!is_readable($file)) {
            throw new DomainException('The Public Suffix List snapshot is unavailable.');
        }

        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!is_array($lines)) {
            throw new DomainException('The Public Suffix List snapshot cannot be read.');
        }

        $rules = ['exact' => [], 'wildcard' => [], 'exception' => []];
        $sawIcann = false;
        $sawPrivate = false;
        $sawEnd = false;
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '// ===BEGIN ICANN DOMAINS===') {
                $sawIcann = true;
            } elseif ($line === '// ===BEGIN PRIVATE DOMAINS===') {
                $sawPrivate = true;
            } elseif ($line === '// ===END PRIVATE DOMAINS===') {
                $sawEnd = true;
            }

            if ($line === '' || strpos($line, '//') === 0) {
                continue;
            }

            if ($line[0] === '!') {
                $rules['exception'][substr($line, 1)] = true;
            } elseif (strpos($line, '*.') === 0) {
                $rules['wildcard'][substr($line, 2)] = true;
            } else {
                $rules['exact'][$line] = true;
            }
        }

        if (!$sawIcann || !$sawPrivate || !$sawEnd ||
            count($rules['exact']) + count($rules['wildcard']) + count($rules['exception']) < 1000
        ) {
            throw new DomainException('The Public Suffix List snapshot is incomplete.');
        }

        self::$publicSuffixRules = $rules;

        return self::$publicSuffixRules;
    }

    /**
     * Normalize a hostname for PSL matching. ASCII and Punycode hosts work
     * without ext-intl; Unicode hostnames require ext-intl's IDNA support.
     *
     * @param string $host
     * @return string
     */
    private function normalizeDomainHost(string $host): string
    {
        $host = strtolower($host);

        if (preg_match('/[^\x00-\x7f]/', $host)) {
            if (!function_exists('idn_to_ascii')) {
                return '';
            }
            $host = idn_to_ascii(
                $host,
                IDNA_NONTRANSITIONAL_TO_ASCII,
                INTL_IDNA_VARIANT_UTS46
            );
            if (!is_string($host)) {
                return '';
            }
            $host = strtolower($host);
        }

        // UTS46 may transform Unicode full-stop variants into ASCII dots.
        // Remove an optional DNS root separator only after normalization.
        if (strpos($host, '..') !== false) {
            return '';
        }
        if (substr($host, -1) === '.') {
            $host = substr($host, 0, -1);
        }

        if ($host === '' || strlen($host) > 253 ||
            filter_var(trim($host, '[]'), FILTER_VALIDATE_IP) !== false ||
            filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) === false
        ) {
            return '';
        }

        return $host;
    }

    /**
     * Resolve a registrable domain (eTLD+1) using the official Public Suffix
     * List rule algorithm, including wildcard, exception and private rules.
     * Public suffixes, IP literals, invalid hosts and single-label hosts
     * return the empty string; IDNs are returned in ASCII/Punycode form.
     *
     * @param string $hostname URL or hostname, or '' to read HTTP_HOST
     * @return string
     * @throws DomainException If the local PSL data is missing or corrupted
     */
    public function getDomainName(string $hostname): string
    {
        $input = $hostname === '' ?
            (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '') : $hostname;
        $parsed = $this->getUrlDomain($input);
        $host = is_string($parsed[0]) ? $this->normalizeDomainHost($parsed[0]) : '';
        if ($host === '') {
            return '';
        }

        $labels = explode('.', $host);
        $labelCount = count($labels);
        if ($labelCount < 2) {
            return '';
        }

        $rules = $this->getPublicSuffixRules();
        $suffixLength = 1; // PSL prevailing implicit '*' rule

        for ($i = 0; $i < $labelCount; $i++) {
            $suffix = implode('.', array_slice($labels, $i));
            $matchedLabels = $labelCount - $i;

            if (isset($rules['exception'][$suffix])) {
                // An exception always wins; exclude its leftmost label.
                $suffixLength = $matchedLabels - 1;
                break;
            }

            if (isset($rules['exact'][$suffix]) && $matchedLabels > $suffixLength) {
                $suffixLength = $matchedLabels;
            }

            if ($i > 0 && isset($rules['wildcard'][$suffix]) &&
                $matchedLabels + 1 > $suffixLength
            ) {
                $suffixLength = $matchedLabels + 1;
            }
        }

        if ($labelCount <= $suffixLength) {
            return '';
        }

        return implode('.', array_slice($labels, -($suffixLength + 1)));
    }

    /**
     * Redirect helper. This method retains the legacy exit behavior.
     *
     * @param string $redirectToUrl
     * @param bool $replaceHeader
     * @param int $responseCode
     */
    public function redirect(string $redirectToUrl = '', bool $replaceHeader = false, int $responseCode = 301)
    {
        header("Location: $redirectToUrl", $replaceHeader, $responseCode);
        die();
    }
}
