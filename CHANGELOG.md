# Changelog

All notable changes to this project will be documented in this file.

## [2.3.3] - 2026-01-18
### Added
- Duplicate candidates CSV export (status-filtered)
- Server-side duplicate review persistence (`duplicate_review`)

### Changed
- Duplicate detection now considers subtitle
- Author identity in duplicate detection is based on normalized `sort_name`
- Duplicate grouping is stable across systems (MySQL / MariaDB)

### Notes
- Existing duplicate reviews must be reset when upgrading to this version.
