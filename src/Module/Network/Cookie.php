<?php

namespace TorneLIB\Module\Network;

/**
 * Cookie configuration and setting helpers.
 *
 * @version 6.1.5
 */
class Cookie
{
    private $cookieDefaultPath = '/';
    private $cookieDefaultDomain;
    private $cookieDomainExplicit = false;
    private $cookieUseSecure;
    private $cookieDefaultPrefix;
    private $cookieTrustedProxies = [];
    private $cookieSecureExplicit = false;

    /**
     * Set a cookie, preserving configured path/domain/secure parameters.
     *
     * @param string $name
     * @param string $value
     * @param int|string $expire
     * @return bool
     */
    public function setCookie($name = '', $value = '', $expire = '')
    {
        // Automatically inferred settings must follow the current request,
        // while explicitly configured values remain persistent.
        $this->setCookieParameters(
            $this->cookieDefaultPath,
            $this->cookieDefaultPrefix,
            $this->cookieDomainExplicit ? $this->cookieDefaultDomain : null,
            $this->cookieSecureExplicit ? $this->cookieUseSecure : null
        );

        if ($expire === '') {
            $expire = time() + 86400;
        } elseif (is_string($expire)) {
            $parsed = ctype_digit($expire) ? (int)$expire : strtotime($expire);
            if ($parsed === false) {
                throw new \InvalidArgumentException('Invalid cookie expiration value.');
            }
            $expire = $parsed;
        }

        return setcookie(
            $this->cookieDefaultPrefix . $name,
            $value,
            $expire,
            $this->cookieDefaultPath,
            $this->cookieDefaultDomain,
            $this->cookieUseSecure
        );
    }

    /**
     * Derive a cookie domain only for valid, registrable DNS hostnames.
     * A public suffix, malformed Host header or IP literal must instead
     * produce a host-only cookie with no Domain attribute.
     *
     * @return string
     */
    private function getAutomaticCookieDomain(): string
    {
        $header = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
        if (!is_string($header) || $header === '' ||
            preg_match('/[\x00-\x20\x7f\/@?#]/', $header)
        ) {
            return '';
        }

        $parsed = parse_url('http://' . $header);
        if (!is_array($parsed) || !isset($parsed['host']) ||
            isset($parsed['user']) || isset($parsed['pass']) ||
            isset($parsed['path']) || isset($parsed['query']) ||
            isset($parsed['fragment'])
        ) {
            return '';
        }

        $host = strtolower($parsed['host']);
        if (strpos($host, '..') !== false) {
            return '';
        }
        if (substr($host, -1) === '.') {
            $host = substr($host, 0, -1);
        }

        if (filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) === false ||
            filter_var(trim($host, '[]'), FILTER_VALIDATE_IP) !== false ||
            strpos($host, '.') === false
        ) {
            return '';
        }

        // The PSL contains private suffixes, so github.io and co.uk are
        // not valid scopes, whereas alice.github.io and example.co.uk are.
        return (new Domain())->getDomainName($host) === '' ? '' : '.' . $host;
    }

    /**
     * Set or refresh cookie defaults. A null domain preserves an existing one
     * or derives a safe default from HTTP_HOST.
     *
     * @param string $path
     * @param string|null $prefix
     * @param string|null $domain
     * @param bool|null $secure
     * @return Cookie
     */
    public function setCookieParameters($path = '/', $prefix = null, $domain = null, $secure = null)
    {
        $this->cookieDefaultPath = $path;

        if ($domain !== null) {
            $this->cookieDefaultDomain = $domain;
            $this->cookieDomainExplicit = true;
        } elseif (!$this->cookieDomainExplicit) {
            $this->cookieDefaultDomain = $this->getAutomaticCookieDomain();
        }

        if ($secure === null) {
            if (!$this->cookieSecureExplicit) {
                $this->cookieUseSecure = Statics::getCurrentServerProtocol(
                    false,
                    $this->cookieTrustedProxies
                );
            }
        } else {
            $this->cookieUseSecure = (bool)$secure;
            $this->cookieSecureExplicit = true;
        }

        if ($prefix !== null) {
            $this->cookieDefaultPrefix = $prefix;
        }

        return $this;
    }

    public function getCookieDefaultPath()
    {
        return $this->cookieDefaultPath;
    }

    public function setCookieDefaultPath($cookieDefaultPath)
    {
        $this->cookieDefaultPath = $cookieDefaultPath;
        return $this;
    }

    public function getCookieDefaultDomain()
    {
        return $this->cookieDefaultDomain;
    }

    public function setCookieDefaultDomain($cookieDefaultDomain)
    {
        $this->cookieDefaultDomain = $cookieDefaultDomain;
        $this->cookieDomainExplicit = $cookieDefaultDomain !== null;
        return $this;
    }

    public function isCookieUseSecure()
    {
        return $this->cookieUseSecure;
    }

    public function setCookieUseSecure($cookieUseSecure)
    {
        $this->cookieUseSecure = $cookieUseSecure === null ? null : (bool)$cookieUseSecure;
        $this->cookieSecureExplicit = $cookieUseSecure !== null;
        return $this;
    }

    /**
     * Configure trusted direct-peer proxies. Forwarded HTTPS is otherwise
     * ignored. An exact IP or a CIDR range may be supplied for IPv4 or IPv6.
     *
     * The reverse proxy must strip client-provided X-Forwarded-Proto headers.
     * An empty array disables all forwarded-scheme trust.
     *
     * @param array $trustedProxies
     * @return Cookie
     * @throws \InvalidArgumentException
     */
    public function setCookieTrustedProxies(array $trustedProxies)
    {
        $address = new Address();
        foreach ($trustedProxies as $trusted) {
            if (!is_string($trusted) || $trusted === '') {
                throw new \InvalidArgumentException('Trusted proxies must be valid IPs or CIDR ranges.');
            }

            if (filter_var($trusted, FILTER_VALIDATE_IP) !== false) {
                continue;
            }

            $parts = explode('/', $trusted);
            if (count($parts) !== 2 ||
                filter_var($parts[0], FILTER_VALIDATE_IP) === false
            ) {
                throw new \InvalidArgumentException('Invalid trusted proxy CIDR.');
            }

            try {
                $address->isIpInRange($parts[0], $trusted);
            } catch (\Exception $exception) {
                throw new \InvalidArgumentException('Invalid trusted proxy CIDR.', 0, $exception);
            }
        }

        $this->cookieTrustedProxies = array_values($trustedProxies);
        if (!$this->cookieSecureExplicit) {
            $this->cookieUseSecure = Statics::getCurrentServerProtocol(
                false,
                $this->cookieTrustedProxies
            );
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getCookieTrustedProxies(): array
    {
        return $this->cookieTrustedProxies;
    }

    public function getCookieDefaultPrefix()
    {
        return $this->cookieDefaultPrefix;
    }

    public function setCookieDefaultPrefix($cookieDefaultPrefix)
    {
        $this->cookieDefaultPrefix = $cookieDefaultPrefix;
        return $this;
    }
}
