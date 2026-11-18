# Changelog

## Unreleased

- Added deterministic PHPUnit coverage for IPv4/IPv6 and ARPA conversions, CIDR ranges and membership, domain parsing and URL extraction, cookie defaults, proxy header collection, protocol detection, legacy facades and error handler restoration.
- Retained legacy live Git tag integration tests behind the explicit `TORNELIB_NETWORK_LIVE_TESTS=1` opt-in.
- Fixed CIDR validation, network alignment, /31 and /32 enumeration, and safe upper bounds on range expansion.
- Fixed URL host validation type safety, URL pagination and common multi-part domain parsing.
- Fixed HTTPS detection, Secure cookie defaults, cookie domain normalization and state preservation across facade calls.
- Removed error handler leakage paths in IPv6 reverse-address and domain validation helpers.
- Aligned documented PHP requirements, PHPUnit configuration and CI runtime matrix.
