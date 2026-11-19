# Changelog

## Unreleased

## 6.1.5 - 2026-11-19

This release includes all previously untagged changes since 6.1.4 (97 commits from 2020-2026), including compatibility maintenance from 2020-2023 and the November 2026 network, domain, cookie-security and test improvements. The minimum declared PHP version is 7.0. PHP 8.6-dev remains an experimental CI probe.

### Packaging

- Removed the obsolete fixed `composer.json` library version so Composer and Packagist infer future releases from Git tags without version mismatches.

### Cookie security and HTTPS proxy handling

- Prevent automatic cookie Domain attributes for public suffixes (including private suffixes), malformed HTTP_HOST values and IP literals. Host-only cookies are used instead.
- Refresh inferred cookie Domain and HTTPS/Secure settings on each cookie emission for safe object reuse across requests while preserving explicit user configuration.
- Add deterministic unit and native HTTP Set-Cookie regression coverage for PSL restrictions and cross-request state.
- Modernized legacy PHPUnit tests with native `test*` methods and removed avoidable `@test`/`@dataProvider` metadata deprecations without losing fixture assertions on PHP >=7.0.
- Restored `$_SERVER` in legacy regression tests and strengthened missing-method exception expectations to prevent test-order dependencies and false passes.

- Completed cookie security regressions #4 and #6: explicit trusted proxy IPv4/IPv6 allowlists for forwarded HTTPS detection; default remains untrusted. Explicit Secure-cookie overrides and legacy Network facades remain supported.
- Added deterministic security checks for direct-peer trust, forged forwarding headers, malformed ranges and real `Set-Cookie` HTTP output (Path, Domain, prefix, Secure, Expires, localhost/IP/missing-host defaults) via an isolated local PHP test server.

### Network, domain and test coverage

- Added deterministic PHPUnit coverage for IPv4/IPv6 and ARPA conversions, CIDR ranges and membership, domain parsing and URL extraction, cookie defaults, proxy header collection, protocol detection, legacy facades and error handler restoration.
- Retained legacy live Git tag integration tests behind the explicit `TORNELIB_NETWORK_LIVE_TESTS=1` opt-in.
- Fixed CIDR validation, network alignment, /31 and /32 enumeration, and safe upper bounds on range expansion.
- Added dual-stack IPv4/IPv6 support to `isIpInRange`, including mixed-family handling and bounded prefixes, with regression tests for partial-byte IPv6 masks.
- Added an experimental PHP 8.6 compatibility probe in CI.
- Fixed URL host validation type safety and URL pagination.
- Replaced the incomplete multi-part suffix heuristic in `Domain::getDomainName()` with full offline Public Suffix List matching, including ICANN/PRIVATE sections, wildcard/exception rules and IDN Punycode support when `ext-intl` is available.
- Bundled an MPL-2.0 PSL snapshot, added a canonical HTTPS update utility and deterministic regression tests covering complex and private suffixes.
- Fixed HTTPS detection, Secure cookie defaults, cookie domain normalization, state preservation across facade calls and correctly forwarded null getter results.
- Removed error handler leakage paths in IPv6 reverse-address and domain validation helpers.
- Aligned documented PHP requirements, PHPUnit configuration and CI runtime matrix.

- Includes previously untagged 2020-2023 compatibility changes for PHP 7 and exception/test maintenance.

Related merged pull requests: #11, #13, #16, #17, #19.
