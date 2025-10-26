# GitHub Setup Guide

This guide explains the one-time setup needed in GitHub for the CTA Highlights plugin's automated workflows.

---

## Quick Answer: Do You Need gh CLI?

**No, you do not need to use the `gh` CLI or create anything directly in GitHub.**

Everything is set up via files that you commit to your repository. GitHub Actions workflows activate automatically once the `.github/workflows/` files are committed and pushed.

---

## What Happens Automatically

Once you commit and push the workflow files, these features work automatically:

### 1. Test Workflow (`.github/workflows/test.yml`)
**Triggers**: Automatically runs on every push or pull request to `main` or `develop` branches

**What it does**:
- Runs PHP CodeSniffer (linting)
- Runs ESLint (JavaScript linting)
- Runs PHPUnit tests (PHP 7.4, 8.0, 8.1, 8.2)
- Runs Jest tests (Node 16, 18, 20)
- Runs Playwright E2E tests
- Uploads coverage reports to Codecov (optional)

**Setup needed**: None - works immediately after commit

### 2. Release Workflow (`.github/workflows/release.yml`)
**Triggers**: Automatically runs when you push a version tag (e.g., `v1.0.0`)

**What it does**:
- Validates version matches between tag and plugin file
- Runs quick tests
- Builds clean plugin ZIP (excludes dev files)
- Generates SHA256 checksum
- Creates GitHub Release
- Attaches ZIP and checksum to release

**Setup needed**: None - works immediately after commit

### 3. WordPress.org Deployment (`.github/workflows/deploy-wporg.yml`)
**Triggers**: Runs when you publish a GitHub release, or manually via Actions tab

**What it does**:
- Deploys plugin to WordPress.org SVN repository
- Creates SVN tag for the version

**Setup needed**: See "Optional Setup" section below

---

## Required Setup (None!)

There is **no required setup** for basic functionality. The test and release workflows will work immediately once you commit the files.

---

## Optional Setup

### Option 1: Code Coverage (Codecov)

If you want code coverage reports uploaded to Codecov:

**Steps**:
1. Go to [codecov.io](https://codecov.io) and sign in with GitHub
2. Add your repository
3. Get your Codecov token
4. Add it as a GitHub secret:
   - Go to your repository on GitHub
   - Navigate to **Settings** → **Secrets and variables** → **Actions**
   - Click **New repository secret**
   - Name: `CODECOV_TOKEN`
   - Value: Your Codecov token

**If you skip this**: Tests will still run, but coverage reports won't be uploaded to Codecov. The workflow will show a warning but won't fail.

### Option 2: WordPress.org Deployment

If you want to automatically deploy to WordPress.org:

**Prerequisites**:
- Your plugin must be approved and listed on WordPress.org
- You need WordPress.org SVN credentials

**Steps**:
1. **Update repository name in workflow**:
   - Edit [.github/workflows/deploy-wporg.yml](.github/workflows/deploy-wporg.yml)
   - Line 19: Change `your-org/cta-highlights` to your actual repository name
   - Example: `github-username/cta-highlights`

2. **Add WordPress.org credentials as secrets**:
   - Go to your repository on GitHub
   - Navigate to **Settings** → **Secrets and variables** → **Actions**
   - Add two secrets:
     - **Name**: `WP_ORG_USERNAME`
       **Value**: Your WordPress.org username
     - **Name**: `WP_ORG_PASSWORD`
       **Value**: Your WordPress.org password

**If you skip this**: GitHub releases will still work perfectly. You just won't have automatic WordPress.org deployment.

---

## First-Time Workflow Activation

GitHub Actions may need to be enabled for your repository:

1. Go to your repository on GitHub
2. Navigate to **Settings** → **Actions** → **General**
3. Under "Actions permissions":
   - Select **"Allow all actions and reusable workflows"**
   - Or select **"Allow [organization] actions and reusable workflows"**
4. Click **Save**

Most repositories have this enabled by default, but check if your workflows aren't running.

---

## How to Use the Automated Workflows

### Running Tests

Tests run automatically on every push/PR, but you can also trigger manually:

1. Go to **Actions** tab on GitHub
2. Select **Tests** workflow
3. Click **Run workflow**
4. Choose branch and click **Run workflow**

### Creating a Release

**Method 1: Via Git Tag (Recommended)**

```bash
# 1. Update version in plugin files
# Edit cta-highlights.php and README.md

# 2. Commit the version bump
git add cta-highlights.php README.md CHANGELOG.md
git commit -m "Bump version to 1.0.0"
git push origin main

# 3. Create and push tag - this triggers the release workflow
git tag -a v1.0.0 -m "Release version 1.0.0"
git push origin v1.0.0

# 4. GitHub Actions automatically:
#    - Validates the version
#    - Runs tests
#    - Builds clean ZIP
#    - Creates GitHub Release with ZIP attachment
```

**Method 2: Via GitHub UI**

1. Go to your repository on GitHub
2. Click **Releases** → **Create a new release**
3. Click **Choose a tag** → Type `v1.0.0` → **Create new tag**
4. Fill in release title and description
5. Click **Publish release**
6. The release workflow runs automatically

**Method 3: Manual Workflow Trigger**

1. Go to **Actions** tab
2. Select **Release** workflow
3. Click **Run workflow**
4. Enter version tag (e.g., `v1.0.0`)
5. Click **Run workflow**

### Deploying to WordPress.org

If you've set up WordPress.org deployment (see "Optional Setup" above):

**Automatic**: Runs automatically when you publish a GitHub release

**Manual**:
1. Go to **Actions** tab
2. Select **Deploy to WordPress.org** workflow
3. Click **Run workflow**
4. Enter version (e.g., `1.0.0` without 'v' prefix)
5. Click **Run workflow**

---

## Verification Checklist

After pushing your workflow files, verify everything is set up:

- [ ] Go to **Actions** tab on GitHub
- [ ] You should see three workflows listed:
  - Tests
  - Release
  - Deploy to WordPress.org
- [ ] Make a test commit to `main` branch
- [ ] Verify **Tests** workflow runs automatically
- [ ] Check workflow completes successfully (green checkmark)

---

## Monitoring Workflow Runs

### View All Workflow Runs
1. Go to **Actions** tab on GitHub
2. See list of all workflow runs with status (success/failure)

### View Specific Run Details
1. Click on a workflow run
2. See job details, logs, and test results
3. Download artifacts (like plugin ZIP or test reports)

### Workflow Notifications
By default, GitHub sends email notifications when workflows fail. Configure in:
- GitHub → Settings → Notifications → Actions

---

## Common Issues

### Workflow Not Running
**Problem**: Pushed workflow files but nothing appears in Actions tab

**Solutions**:
1. Check Actions are enabled (Settings → Actions → General)
2. Verify workflow YAML syntax is valid
3. Check branch protection rules aren't blocking Actions
4. Make sure `.github/workflows/` path is correct

### Release Workflow Fails: "Version mismatch"
**Problem**: Git tag doesn't match version in plugin file

**Solution**:
```bash
# Tag format: v1.0.0 (with 'v' prefix)
# Plugin file version: 1.0.0 (without 'v' prefix)

# Check version in cta-highlights.php:
grep "Version:" cta-highlights.php

# If mismatch, update file and re-create tag:
git tag -d v1.0.0  # Delete local tag
git push origin :refs/tags/v1.0.0  # Delete remote tag
# ... fix version in file ...
git tag -a v1.0.0 -m "Release 1.0.0"
git push origin v1.0.0
```

### WordPress.org Deployment Fails
**Problem**: "Error authenticating with SVN"

**Solutions**:
1. Verify `WP_ORG_USERNAME` and `WP_ORG_PASSWORD` secrets are correct
2. Test credentials locally:
   ```bash
   svn co https://plugins.svn.wordpress.org/cta-highlights
   # Enter your WordPress.org username/password
   ```
3. Make sure your plugin is approved on WordPress.org
4. Check repository name is updated in `deploy-wporg.yml` line 19

### E2E Tests Timeout
**Problem**: Playwright tests fail with timeout errors

**Solution**: The `test.yml` workflow includes a WordPress readiness check. If it still fails:
1. Increase timeout in workflow (currently 60 seconds)
2. Check `@wordpress/env` is working correctly locally
3. Review Playwright test artifacts uploaded to failed workflow runs

---

## Workflow File Locations

All workflows are in `.github/workflows/` directory:

- [test.yml](.github/workflows/test.yml) - Automated testing
- [release.yml](.github/workflows/release.yml) - GitHub release creation
- [deploy-wporg.yml](.github/workflows/deploy-wporg.yml) - WordPress.org deployment

---

## Additional Resources

- [GitHub Actions Documentation](https://docs.github.com/en/actions)
- [WordPress.org Plugin Deployment](https://developer.wordpress.org/plugins/wordpress-org/how-to-use-subversion/)
- [Release Process Guide](RELEASE.md) - Complete release workflow documentation
- [Testing Guide](tests/TESTING-GUIDE.md) - Running tests locally

---

## Summary: What You Need to Do

### Immediately (To Start Using):
1. ✅ Commit and push the workflow files (already done if you're reading this)
2. ✅ That's it! Tests and releases work automatically now

### Optional (If Desired):
1. 🔧 Add `CODECOV_TOKEN` secret for code coverage reports
2. 🔧 Update repository name in `deploy-wporg.yml` (line 19)
3. 🔧 Add `WP_ORG_USERNAME` and `WP_ORG_PASSWORD` secrets for WordPress.org deployment

### You Do NOT Need To:
- ❌ Use `gh` CLI
- ❌ Create anything manually in GitHub
- ❌ Configure webhooks
- ❌ Set up GitHub Apps
- ❌ Install any GitHub integrations

**Everything works through committed files and git tags!**

---

**Questions?** Check the [Release Process Guide](RELEASE.md) or GitHub Actions logs for troubleshooting.
