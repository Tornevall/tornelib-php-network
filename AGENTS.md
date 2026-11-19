# Repository instructions

This repository maintains the TorneLIB network utility library and its compatibility facade.

- Keep code and code comments in English.
- Preserve backward compatibility with the documented PHP runtime floor and both `TorneLIB\Module\Network` and `TorneLIB\MODULE_NETWORK` entry points unless a change explicitly documents a deliberate break.
- Add deterministic PHPUnit regression tests for bug fixes and tests for newly added public behavior.
- Do not make unit tests depend on public DNS, external sites, internet access or production services.
- Restore global state modified by tests, including `$_SERVER`, HTTP headers and error handlers.
- Validate IP and CIDR input before bitwise operations or allocation. Treat authentication and cookie-security behavior as high risk.
- Keep CI configuration, composer requirements, README and CHANGELOG aligned with actual supported behavior.
- Avoid external provider/live verification for changes fully testable deterministically.
- Keep `resources/public_suffix_list.dat` a full PSL snapshot (ICANN and PRIVATE) derived from https://publicsuffix.org/list/public_suffix_list.dat using `bin/update-public-suffix-list.php`. Preserve its MPL-2.0 licensing header. Unit tests must never refresh PSL or depend on the network.
- Verify the documented `getDomainName()` registrable-domain contract with wildcard, exception, private suffix and IDN test cases before editing its behavior.
- No API versioning is introduced by maintenance changes.
