# Changelog

## Unreleased

- Added deterministic PHPUnit coverage for IPv4/IPv6 and ARPA conversions, CIDR ranges and membership, domain parsing and URL extraction, cookie defaults, proxy header collection, protocol detection, legacy facades and error handler restoration.
- Retained legacy live Git tag integration tests behind the explicit `TORNELIB_NETWORK_LIVE_TESTS=1` opt-in.
- Fixed CIDR validation, network alignment, /31 and /32 enumeration, and safe upper bounds on range expansion.
- Added dual-stack IPv4/IPv6 support to `isIpInRange`, including mixed-family handling and bounded prefixes, with regression tests for partial-byte IPv6 masks.
- Added an experimental PHP 8.6 compatibility probe in CI.
- Fixed URL host validation type safety, URL pagination and common multi-part domain parsing.
- Fixed HTTPS detection, Secure cookie defaults, cookie domain normalization, state preservation across facade calls and correctly forwarded null getter results.
- Removed error handler leakage paths in IPv6 reverse-address and domain validation helpers.
- Aligned documented PHP requirements, PHPUnit configuration and CI runtime matrix.
