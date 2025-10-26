# Changelog

All notable changes to the CTA Highlights plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Planned
- WordPress.org plugin directory submission
- Additional template examples
- Performance optimizations

## [0.1.0] - 2025-10-26

### Added
- Initial pre-production release
- Core shortcode `[cta_highlights]` functionality
- Inline CTA display with optional highlight effect
- Template system with theme override support
- Smart cooldown system (global and template-specific)
- Cookie and localStorage-based cooldown tracking
- Auto-insertion feature with display conditions
- LocalStorage conditions for advanced targeting
- Fallback chain system for CTAs
- Custom post type for managing CTAs
- Admin UI for auto-insertion configuration
- Accessibility features (ARIA, keyboard navigation, focus trapping)
- Customizable CSS custom properties
- Helper functions for developers
- Comprehensive filter and action hooks
- Complete testing infrastructure:
  - PHPUnit unit and integration tests
  - Jest JavaScript unit tests
  - Playwright E2E tests
  - Visual regression testing
  - Multi-version PHP testing (7.4, 8.0, 8.1, 8.2)
  - Multi-version Node testing (16, 18, 20)
- GitHub Actions CI/CD workflows:
  - Automated testing on push/PR
  - Automated release creation on tags
  - Optional WordPress.org deployment
- Build automation scripts
- Complete documentation:
  - README with feature documentation
  - Testing guide
  - Release process guide
  - GitHub setup guide
  - CLAUDE.md for AI assistance context

### Security
- Proper output escaping and sanitization
- Nonce verification for admin actions
- Capability checks for admin features
- XSS protection in templates

### Performance
- Asset loading only when shortcode is present
- Template caching
- Optimized JavaScript with minimal dependencies
- Lazy-loaded admin assets

---

## Versioning Strategy

This plugin follows [Semantic Versioning](https://semver.org/):

- **0.x.x** - Pre-production development versions
- **1.0.0** - First production-ready release (planned)
- **MAJOR.MINOR.PATCH** format where:
  - MAJOR = Incompatible API changes
  - MINOR = New functionality (backward compatible)
  - PATCH = Bug fixes (backward compatible)

---

[Unreleased]: https://github.com/your-org/cta-highlights/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/your-org/cta-highlights/releases/tag/v0.1.0
