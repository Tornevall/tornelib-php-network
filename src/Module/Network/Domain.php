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
     * Validate a parsed URL host against available DNS records.
     * No process-global PHP error handler is installed by this method.
     *
     * @param string $requestedUrl
     * @param array|false $parsed
     * @throws DomainException
     */
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

        $records = @dns_get_record($parsed['host'], DNS_ANY);
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
     * Extract a best-effort base domain. Returns '' for local hosts and IP literals.
     *
     * The listed multi-label public suffixes are a compatibility fallback, not
     * the complete Public Suffix List. Callers making security decisions must
     * use a full, maintained PSL implementation.
     *
     * @param string $hostname
     * @return string
     * @throws Exception
     */
    public function getDomainName(string $hostname): string
    {
        $input = $hostname === '' ? (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '') : $hostname;
        $parsed = $this->getUrlDomain($input);
        $host = is_string($parsed[0]) ? strtolower(rtrim($parsed[0], '.')) : '';

        if ($host === '' ||
            filter_var(trim($host, '[]'), FILTER_VALIDATE_IP) !== false ||
            strpos($host, '.') === false ||
            filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) === false
        ) {
            return '';
        }

        $labels = explode('.', $host);
        $lastTwo = implode('.', array_slice($labels, -2));
        $multiPartSuffixes = [
            'co.uk', 'org.uk', 'gov.uk', 'ac.uk', 'net.uk',
            'com.au', 'net.au', 'org.au', 'edu.au', 'gov.au',
            'co.nz', 'org.nz', 'govt.nz', 'com.br', 'com.cn',
            'co.jp', 'co.kr', 'co.in', 'co.za', 'com.sg',
        ];

        $requiredLabels = in_array($lastTwo, $multiPartSuffixes, true) ? 3 : 2;

        return count($labels) >= $requiredLabels ?
            implode('.', array_slice($labels, -$requiredLabels)) : '';
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
