# Changelog

All notable changes to this project will be documented in this file.

## [2.3.4] - 2026-01-18
### Added
- Status endpoint reports app and schema versions

### Changed
- Established a cross-platform baseline schema for macos and fedora
- SystemInfo is checked for version correctness on status

## [2.3.3] - 2026-01-18
### Added
- Duplicate candidates CSV export (status-filtered)
- Server-side duplicate review persistence (`duplicate_review`)

### Changed
- Duplicate detection now considers subtitle
- Author identity in duplicate detection is based on normalized `sort_name`
- Duplicate grouping is stable across systems (MySQL / MariaDB)
- SystemInfo schema/app versions are synchronized on login

### Notes
- Existing duplicate reviews must be reset when upgrading to this version.
