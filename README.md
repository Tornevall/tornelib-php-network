# tornevall/tornelib-php-network

## A former part of tornevall/tornelib-php-netcurl

Network 6.1.x extracts network utility functions from NetCurl so they can be maintained independently of the HTTP transport. The library provides IP and CIDR helpers, DNS/domain utilities, proxy header inspection, HTTPS detection, cookie configuration and compatibility facades.

## Requirements

PHP 7.0 or newer, matching \`composer.json\`. The supported compatibility surface is maintained by testing both legacy and current public entry points.

## Testing

Install dependencies with Composer, and run PHPUnit using a version compatible with your PHP runtime:

\`\`\`sh
composer install
phpunit --configuration phpunit.xml
\`\`\`

The default suite runs deterministically without external DNS or live network services. Three legacy Git tag integration tests are retained but skipped unless you explicitly opt in:

\`\`\`sh
TORNELIB_NETWORK_LIVE_TESTS=1 phpunit --configuration phpunit.xml
\`\`\`

Live tests require the external Git hosts and associated NetCurl utilities to be available.

### Security and compatibility notes

- The \`Proxy\` class returns raw request header values; forwarded headers are untrusted unless the application independently verifies its reverse proxy.
- \`isIpInRange\` validates IPv4 CIDRs only. Invalid addresses or masks produce a library exception.
- \`getRangeFromMask\` returns *all* addresses in a small IPv4 range, including first and last addresses. Ranges above 65536 addresses are rejected to avoid unbounded memory allocation.
- \`Domain::getDomainName\` uses a small compatibility list for common multi-part suffixes. It is not a Public Suffix List implementation and must not be used as a security boundary. Applications needing registrable-domain decisions should use a maintained PSL library.
- \`MODULE_NETWORK\` remains the legacy entry point and delegates to \`Module\Network\`.
