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
    private $cookieUseSecure;
    private $cookieDefaultPrefix;

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
        if ($this->cookieDefaultDomain === null || $this->cookieUseSecure === null) {
            $this->setCookieParameters(
                $this->cookieDefaultPath,
                $this->cookieDefaultPrefix,
                $this->cookieDefaultDomain,
                $this->cookieUseSecure
            );
        }

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
        } elseif ($this->cookieDefaultDomain === null) {
            $httpHost = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
            $parsedHost = parse_url('http://' . $httpHost, PHP_URL_HOST);
            $host = is_string($parsedHost) ? strtolower(rtrim($parsedHost, '.')) : '';

            if (filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) !== false &&
                filter_var(trim($host, '[]'), FILTER_VALIDATE_IP) === false &&
                strpos($host, '.') !== false
            ) {
                $this->cookieDefaultDomain = '.' . $host;
            } else {
                // An empty Domain attribute produces a host-only cookie.
                $this->cookieDefaultDomain = '';
            }
        }

        if ($secure === null) {
            if ($this->cookieUseSecure === null) {
                $this->cookieUseSecure = Statics::getCurrentServerProtocol();
            }
        } else {
            $this->cookieUseSecure = (bool)$secure;
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
        return $this;
    }

    public function isCookieUseSecure()
    {
        return $this->cookieUseSecure;
    }

    public function setCookieUseSecure($cookieUseSecure)
    {
        $this->cookieUseSecure = $cookieUseSecure;
        return $this;
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
