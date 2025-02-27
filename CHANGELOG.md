# Changelog

All notable changes to this project will be documented in this file.

## [1.0.0] - 2025-02-27

### Added

- **Initial Release:**
    - Complete implementation of GitIgnore PHP.
    - Parsing of `.gitignore` files along with support for additional ignore files (e.g. `.ctreeignore`).

- **Advanced Glob Pattern Matching:**
    - Single (`*`) and double (`**`) asterisk wildcards.
    - Single-character matching using `?`.

- **Brace Expansion:**
    - Support for advanced brace expansion (e.g. `*.{js,css,html}` expands to `*.js`, `*.css`, and `*.html`).
    - Nested brace expansion support.

- **Negation Rules and Directory-only Rules:**
    - Support for negation rules using `!` to re-include files.
    - Directory-only rules using trailing slashes (e.g. `build/`).

- **Configurable Case Sensitivity:**
    - Ability to toggle between case-sensitive and case-insensitive matching.

- **Symfony Finder Integration:**
    - Seamless integration with Symfony's Finder component for efficient file system scanning and filtering.

- **Comprehensive Testing:**
    - Extensive test suite covering various scenarios, including edge cases, special characters, Unicode support, and complex pattern combinations.