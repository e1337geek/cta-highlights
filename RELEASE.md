# Release Process

This document explains how to create and publish new releases of the CTA Highlights plugin.

---

## Overview

The plugin uses automated workflows for releases:
- **GitHub Releases** - Automated ZIP creation on git tags
- **WordPress.org** - Optional automated deployment to WordPress.org SVN

---

## Prerequisites

### 1. Version Numbers

The plugin uses [Semantic Versioning](https://semver.org/):
- **MAJOR.MINOR.PATCH** (e.g., `1.2.3`)
- **MAJOR**: Breaking changes
- **MINOR**: New features (backwards compatible)
- **PATCH**: Bug fixes

### 2. Required Files

- `cta-highlights.php` - Contains version number in header
- `README.md` - Contains "Stable tag" version
- `CHANGELOG.md` (optional) - Version changelog

---

## Release Checklist

### Before Release

- [ ] All tests pass locally (`npm run test:all`)
- [ ] Code is linted (`npm run lint`)
- [ ] Version bumped in `cta-highlights.php`
- [ ] Version bumped in `README.md` (Stable tag)
- [ ] `CHANGELOG.md` updated with changes
- [ ] Documentation updated if needed
- [ ] All changes committed to `main` branch

### During Release

- [ ] Create git tag (see below)
- [ ] Push tag to GitHub
- [ ] Wait for GitHub Actions to complete
- [ ] Verify GitHub Release created
- [ ] Download and test ZIP
- [ ] (Optional) Deploy to WordPress.org

### After Release

- [ ] Test plugin in clean WordPress install
- [ ] Announce release (if public)
- [ ] Close milestone (if using GitHub milestones)

---

## Step-by-Step Release Process

### 1. Update Version Numbers

**In `cta-highlights.php`:**
```php
/**
 * Version: 1.2.3
 */
```

**In `README.md`:**
```markdown
Stable tag: 1.2.3
```

### 2. Update Changelog

**Create/update `CHANGELOG.md`:**
```markdown
## [1.2.3] - 2025-01-26

### Added
- New feature description

### Fixed
- Bug fix description

### Changed
- Change description
```

### 3. Commit Changes

```bash
git add cta-highlights.php README.md CHANGELOG.md
git commit -m "Bump version to 1.2.3"
git push origin main
```

### 4. Create Git Tag

```bash
# Create annotated tag
git tag -a v1.2.3 -m "Release version 1.2.3"

# Push tag to GitHub
git push origin v1.2.3
```

**Alternatively, create tag via GitHub UI:**
1. Go to GitHub → Releases → "Create a new release"
2. Click "Choose a tag" → Type `v1.2.3` → "Create new tag"
3. Add release title and notes
4. Click "Publish release"

### 5. GitHub Actions Runs Automatically

The `.github/workflows/release.yml` workflow will:
1. Checkout code
2. Validate version matches tag
3. Run tests
4. Build clean ZIP (excludes dev files)
5. Create GitHub Release with ZIP attachment
6. Generate SHA256 checksum

**Monitor progress:**
- Go to GitHub → Actions tab
- Watch the "Release" workflow

### 6. Verify Release

Once the workflow completes:
1. Go to GitHub → Releases
2. Find your release (e.g., `v1.2.3`)
3. Download `cta-highlights-1.2.3.zip`
4. Test the ZIP:
   ```bash
   # Extract and inspect
   unzip cta-highlights-1.2.3.zip
   cd cta-highlights
   ls -la

   # Verify no dev files
   # Should NOT contain: tests/, node_modules/, .git/, etc.
   ```

### 7. Deploy to WordPress.org (Optional)

**If your plugin is listed on WordPress.org:**

#### Manual Deployment:
```bash
# 1. Checkout WordPress.org SVN
svn co https://plugins.svn.wordpress.org/cta-highlights svn

# 2. Copy files to trunk
rsync -av --delete \
  --exclude='.svn' \
  --exclude='.git' \
  --exclude='tests' \
  --exclude='node_modules' \
  . svn/trunk/

# 3. Create tag
cd svn
svn cp trunk tags/1.2.3

# 4. Commit to SVN
svn ci -m "Release 1.2.3"
```

#### Automated Deployment:

The `.github/workflows/deploy-wporg.yml` workflow can deploy automatically:

**Setup (one-time):**
1. Go to GitHub → Settings → Secrets → Actions
2. Add secrets:
   - `WP_ORG_USERNAME`: Your WordPress.org username
   - `WP_ORG_PASSWORD`: Your WordPress.org password

**Deploy:**
- Runs automatically when you publish a GitHub release
- OR trigger manually from GitHub Actions tab

---

## Local Build Testing

Before creating a release, test the build process locally:

```bash
# Build ZIP locally
npm run build:zip

# Verify contents
npm run build:zip:verify

# The ZIP will be in build/cta-highlights-{version}.zip
```

**Test the local ZIP:**
1. Install in clean WordPress
2. Activate plugin
3. Test core functionality
4. Check for errors in console/logs

---

## What Gets Included in Release

The `.distignore` and `.gitattributes` files control what's excluded:

### ✅ Included:
- `cta-highlights.php` (main plugin file)
- `includes/` (PHP source)
- `assets/` (CSS, JS, images)
- `templates/` (template files)
- `README.md`
- `uninstall.php`
- `vendor/` (production Composer dependencies)

### ❌ Excluded:
- `.git/` and `.github/`
- `tests/` (all test files)
- `node_modules/`
- `composer.json`, `package.json` (dev config)
- `.wp-env.json`, `phpunit.xml`, etc.
- `bin/`, `build/`, `coverage/`
- IDE files (`.vscode/`, `.idea/`)

---

## Rollback Process

If a release has issues:

### GitHub Release:
1. Delete the release from GitHub
2. Delete the git tag:
   ```bash
   git tag -d v1.2.3
   git push origin :refs/tags/v1.2.3
   ```
3. Fix issues and re-release

### WordPress.org:
1. WordPress.org doesn't allow deleting versions
2. Create a new patch version (e.g., `1.2.4`) with fixes
3. Release new version

---

## Hotfix Process

For urgent bug fixes on released version:

```bash
# 1. Create hotfix branch from tag
git checkout -b hotfix/1.2.4 v1.2.3

# 2. Make fixes
# ... edit files ...

# 3. Update version to 1.2.4
# ... edit cta-highlights.php and README.md ...

# 4. Commit and merge
git commit -am "Fix critical bug"
git checkout main
git merge hotfix/1.2.4

# 5. Tag and release
git tag -a v1.2.4 -m "Hotfix release 1.2.4"
git push origin main v1.2.4
```

---

## Troubleshooting

### Release Workflow Fails

**"Version mismatch" error:**
- Ensure version in `cta-highlights.php` matches git tag
- Tag format: `v1.2.3` (with 'v' prefix)
- Plugin version: `1.2.3` (without 'v' prefix)

**Tests fail:**
- Fix tests before creating tag
- Or delete tag, fix, and re-create

**ZIP not created:**
- Check GitHub Actions logs
- Verify permissions
- Ensure `GITHUB_TOKEN` has write access

### WordPress.org Deployment Fails

**SVN credentials wrong:**
- Verify `WP_ORG_USERNAME` and `WP_ORG_PASSWORD` secrets
- Test credentials locally: `svn co https://plugins.svn.wordpress.org/cta-highlights`

**Plugin not approved yet:**
- You must have an approved plugin on WordPress.org first
- Apply at: https://wordpress.org/plugins/developers/add/

---

## GitHub Releases vs WordPress.org

| Feature | GitHub | WordPress.org |
|---------|--------|---------------|
| **Audience** | Developers, advanced users | End users |
| **Format** | ZIP file | SVN repository |
| **Automation** | Full (on git tag push) | Optional (requires secrets) |
| **Hosting** | GitHub Releases | WordPress.org servers |
| **Updates** | Manual download | Auto-update in WordPress |
| **Required** | Yes (for version control) | No (optional) |

**Recommendation:** Always create GitHub releases. Only deploy to WordPress.org if you have a public plugin listing.

---

## Version History

Example `CHANGELOG.md` format:

```markdown
# Changelog

All notable changes to this project will be documented in this file.

## [1.2.3] - 2025-01-26

### Added
- Visual regression testing
- Performance benchmarks

### Fixed
- E2E test timeout issues

## [1.2.2] - 2025-01-20

### Fixed
- Security fix for XSS vulnerability

## [1.2.1] - 2025-01-15

### Changed
- Updated dependencies

## [1.2.0] - 2025-01-10

### Added
- Auto-insertion feature
- Fallback CTA chains

## [1.1.0] - 2025-01-05

### Added
- Template override system
- Cooldown functionality

## [1.0.0] - 2025-01-01

### Added
- Initial release
- Shortcode support
- Highlight effect
```

---

## Support

For questions about the release process:
- Check GitHub Actions logs
- Review `.github/workflows/` files
- Consult `tests/TESTING-GUIDE.md` for test information

---

**Quick Reference:**

```bash
# Complete release flow
npm run test:all           # Run all tests
npm run lint              # Check code style
npm run build:zip         # Test local build
git tag -a v1.2.3 -m "Release 1.2.3"
git push origin v1.2.3    # Triggers automated release
```

**End of Release Process Documentation**
