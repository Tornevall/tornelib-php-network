# tornevall/tornelib-php-network

## A former part of tornevall/tornelib-php-netcurl

Network 6.1.x extracts network utility functions from NetCurl so they can be maintained independently of the HTTP transport. The library provides IP and CIDR helpers, DNS/domain utilities, proxy header inspection, HTTPS detection, cookie configuration and compatibility facades.

## Requirements

PHP 7.0 or newer, matching `composer.json`. The supported compatibility surface is maintained by testing both legacy and current public entry points.

Published library versions are derived from Git tags by Composer/Packagist; `composer.json` intentionally has no hard-coded `version` field. Do not add or rewrite tags for unreleased work. Release publication requires a new, immutable tag after the intended changes are merged and verified.

## Testing

Install dependencies with Composer, and run PHPUnit using a version compatible with your PHP runtime:

```sh
composer install
phpunit --configuration phpunit.xml
```

The default suite runs deterministically without external DNS or live network services. Three legacy Git tag integration tests are retained but skipped unless you explicitly opt in:

```sh
TORNELIB_NETWORK_LIVE_TESTS=1 phpunit --configuration phpunit.xml
```

Live tests require the external Git hosts and associated NetCurl utilities to be available.

### Security and compatibility notes

- The `Proxy` class returns raw request header values. Forwarded protocol headers are ignored by default. Explicitly allowlisting a direct peer's IP/CIDR permits `X-Forwarded-Proto: https` to count as HTTPS. A trusted reverse proxy **must overwrite or strip client-supplied forwarding headers**; never trust `X-Forwarded-For` as the direct peer.
- `Cookie::setCookieTrustedProxies(array $allowlist)` configures the optional trusted proxy ranges (IPv4 or IPv6) for automatic Secure-cookie defaults. For example:

```php
$cookie = new \TorneLIB\Module\Network\Cookie();
$cookie->setCookieTrustedProxies(['192.0.2.0/24', '2001:db8::/32']);
$cookie->setCookieDefaultPath('/app');
$cookie->setCookie('session', 'token');
```

- The same proxy allowlist can be supplied explicitly to `Statics::getCurrentServerProtocol(false, $allowlist)`, `Network::getIsHttps($allowlist)` and `Network::getProtocol($allowlist)`. Invalid proxy ranges passed to `setCookieTrustedProxies()` throw `InvalidArgumentException`. An empty list disables forwarding trust. An explicit cookie Secure override still wins over auto-detection. This does **not** make an untrusted HTTP connection secure; TLS must be handled at the trusted proxy.
- The test suite verifies native `Set-Cookie` headers using a short-lived PHP built-in HTTP server bound to `127.0.0.1` (requires CLI process and loopback socket support). It checks Path, Domain, prefix, Secure, expiry and host-only behavior across PHP runtimes.
- `isIpInRange` accepts IPv4 and IPv6 CIDRs (`/0` to `/32` and `/0` to `/128` respectively), rejecting invalid input with a library exception and returning `false` for valid mixed-family comparisons. IPv4-mapped IPv6 addresses are treated as IPv6, not coerced into IPv4.
- PHP 8.6 is included in CI as an experimental compatibility probe while the first stable release is being published. It is not yet a separately guaranteed runtime.
- `getRangeFromMask` returns *all* addresses in a small IPv4 range, including first and last addresses. Ranges above 65536 addresses are rejected to avoid unbounded memory allocation.
- `Domain::getDomainName` uses the complete bundled Public Suffix List (PSL) snapshot, including ICANN and PRIVATE domains, wildcard rules and exception rules, to return the registrable domain (eTLD+1). `www.example.co.uk` returns `example.co.uk`, `a.www.ck` returns `www.ck`, and `one.alice.github.io` returns `alice.github.io`. A public suffix itself, a single-label host, an IP address or an invalid hostname returns `''`. For unknown TLDs, the PSL's implicit `*` rule applies.
- PSL domain output is lowercase ASCII/Punycode. Internationalized Unicode hostnames require the optional PHP `ext-intl` extension (UTS46 IDNA). Without it, Unicode input fails closed with `''`; ASCII and Punycode hostnames continue working. Never use eTLD+1 alone as an authorization or cookie-domain trust decision.
- The bundled `resources/public_suffix_list.dat` is an offline, generated PSL snapshot under the Mozilla Public License 2.0. To refresh its ICANN and PRIVATE rules from the canonical upstream endpoint, run `php bin/update-public-suffix-list.php` in a PHP CLI with `ext-intl` and outbound HTTPS. Review and commit the regenerated snapshot. Production lookups never require network access.
- `MODULE_NETWORK` remains the legacy entry point and delegates to `Module\Network`.
