<?php

namespace TorneLIB\Module\Network;

use Exception;
use TorneLIB\Exception\Constants;
use TorneLIB\Exception\ExceptionHandler;
use TorneLIB\Module\Exception\DomainException;

/**
 * Class Address Internet addressing handler.
 *
 * @package TorneLIB\Module\Network
 * @version 6.1.5
 */
class Domain
{
    /**
     * @var
     * @since 6.1.5
     */
    private $domainExceptionHandler;

    /**
     * @var
     * @since 6.1.5
     */
    private $domainException;

    /**
     * @throws ExceptionHandler
     */
    private function getNetworkErrorHandler()
    {
        $this->domainExceptionHandler = set_error_handler(function ($errNo, $errStr) {
            if (empty($this->stremWarningException['string'])) {
                $this->domainException['code'] = $errNo;
                $this->domainException['string'] = $errStr;
            }
            restore_error_handler();
            throw new DomainException($errStr, $errNo);
        }, E_WARNING);
    }

    /**
     * Validate if hostname in domain exists in DNS entries.
     *
     * @param string $urlParsedHost
     * @param string $urlParsed
     * @throws ExceptionHandler
     * @noinspection PhpIllegalStringOffsetInspection
     */
    private function getUrlDomainValidatedHost(string $urlParsedHost, string $urlParsed)
    {
        $this->getNetworkErrorHandler();

        // Make sure that the host is not invalid
        if (isset($urlParsed['host']) && filter_var($urlParsedHost, FILTER_VALIDATE_URL)) {
            $hostRecord = dns_get_record($urlParsed['host'], DNS_ANY);
            restore_error_handler();
            if (!count($hostRecord)) {
                throw new DomainException(
                    sprintf(
                        'Host validation for "%s" in %s failed.',
                        $urlParsedHost,
                        __FUNCTION__
                    ),
                    Constants::LIB_NETCURL_DOMAIN_OR_HOST_VALIDATION_FAILURE
                );
            }
        } else {
            throw new DomainException(
                sprintf(
                    'Host validation for "%s" in %s failed.',
                    $urlParsedHost,
                    __FUNCTION__
                ),
                Constants::LIB_NETCURL_DOMAIN_OR_HOST_VALIDATION_FAILURE
            );
        }
    }

    /**
     * @param $stringWithUrls
     * @param array $protocols
     * @return array
     */
    private function getUrlsByProtocol($stringWithUrls, $protocols = ['http'])
    {
        $return = [];
        foreach ($protocols as $protocol) {
            $regex = "@[\"|\']$protocol(.*?)[\"|\']@is";
            preg_match_all($regex, $stringWithUrls, $matches);
            $urls = [];
            if (isset($matches[1]) && count($matches[1])) {
                $urls = $matches[1];
            }
            if (count($urls)) {
                foreach ($urls as $url) {
                    $trimUrl = trim($url);
                    if (!empty($trimUrl)) {
                        $prependUrl = $protocol . $url;
                        if (!in_array($prependUrl, $return)) {
                            $return[] = $prependUrl;
                        }
                    }
                }
            }
        }

        return $return;
    }

    /**
     * @param $returnArray
     * @param $offset
     * @param $urlLimit
     * @return array
     */
    private function getUrlsByOffset($returnArray, $offset, $urlLimit)
    {
        $return = $returnArray;

        if (count($returnArray) && $offset > -1 && $offset <= $returnArray) {
            $allowedOffset = 0;
            $returnNewArray = [];
            $urlCount = 0;
            for ($offsetIndex = 0; $offsetIndex < count($returnArray); $offsetIndex++) {
                if ($offsetIndex == $offset) {
                    $allowedOffset = true;
                }
                if ($allowedOffset) {
                    // Break when requested limit has beenreached
                    $urlCount++;
                    if ($urlLimit > -1 && $urlCount > $urlLimit) {
                        break;
                    }
                    $returnNewArray[] = $returnArray[$offsetIndex];
                }
            }
            $return = $returnNewArray;
        }

        return $return;
    }


    /**
     * Returns minimalistic parsed info about a hostname or url.
     *
     * @param string $requestedUrlHost
     * @param bool $validateHost
     * @return array
     * @throws ExceptionHandler
     */
    public function getUrlDomain($requestedUrlHost = '', $validateHost = false): array
    {
        // If the scheme is forgotten, add it to keep normal hosts validatable too.
        if (!preg_match("/\:\/\//", $requestedUrlHost)) {
            $requestedUrlHost = sprintf(
                'http://%s',
                $requestedUrlHost
            );
        }
        $urlParsed = parse_url($requestedUrlHost);
        if ($validateHost) {
            $this->getUrlDomainValidatedHost($requestedUrlHost, $urlParsed);
        }

        return [
            isset($urlParsed['host']) ? $urlParsed['host'] : null,
            isset($urlParsed['scheme']) ? $urlParsed['scheme'] : null,
            isset($urlParsed['path']) ? $urlParsed['path'] : null,
        ];
    }

    /**
     * @param string $stringWithUrls
     * @param int $offset
     * @param int $urlLimit
     * @param array $protocols
     * @return array
     * @since 5.0.0
     */
    public function getUrlsFromHtml(
        string $stringWithUrls,
        int $offset = -1,
        int $urlLimit = -1,
        array $protocols = ['http']
    ): array {
        $urlList = $this->getUrlsByProtocol($stringWithUrls, $protocols);

        return $this->getUrlsByOffset($urlList, $offset, $urlLimit);
    }

    /**
     * Extract domain name (zone name) from hostname
     *
     * @param string $hostname Alternative hostname than the HTTP_HOST
     * @return string
     * @throws Exception
     * @since 5.0.0
     */
    public function getDomainName(string $hostname): string
    {
        $return = null;
        $currentHost = "";
        if (empty($hostname)) {
            if (isset($_SERVER['HTTP_HOST'])) {
                $currentHost = $_SERVER['HTTP_HOST'];
            }
        } else {
            $extractHost = $this->getUrlDomain($hostname);
            $currentHost = $extractHost[0];
        }
        // Do this, only if it's a real domain (if scripts are running from console, there might be a loss of this
        // hostname (or if it is a single name, like localhost).
        if (!empty($currentHost) && preg_match("/\./", $currentHost)) {
            $thisDomainArray = explode(".", $currentHost);
            if (is_array($thisDomainArray)) {
                $return = sprintf(
                    '%s.%s',
                    $thisDomainArray[count($thisDomainArray) - 2],
                    $thisDomainArray[count($thisDomainArray) - 1]
                );
            }
        }

        return $return;
    }

    /**
     * Redirect helper.
     * @param string $redirectToUrl
     * @param bool $replaceHeader
     * @param int $responseCode
     * @since 5.0.0
     */
    public function redirect(string $redirectToUrl = '', bool $replaceHeader = false, int $responseCode = 301)
    {
        header("Location: $redirectToUrl", $replaceHeader, $responseCode);
        die();
    }
}
