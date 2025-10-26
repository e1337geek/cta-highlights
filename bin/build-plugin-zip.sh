#!/bin/bash
# Build clean WordPress plugin ZIP for distribution
# Usage: bash bin/build-plugin-zip.sh

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Plugin configuration
PLUGIN_SLUG="cta-highlights"
PLUGIN_FILE="cta-highlights.php"
BUILD_DIR="build"
RELEASE_DIR="$BUILD_DIR/$PLUGIN_SLUG"

# Get version from plugin file
if [ ! -f "$PLUGIN_FILE" ]; then
    echo -e "${RED}Error: Plugin file not found: $PLUGIN_FILE${NC}"
    exit 1
fi

VERSION=$(grep "Version:" "$PLUGIN_FILE" | head -1 | awk '{print $3}')
if [ -z "$VERSION" ]; then
    echo -e "${RED}Error: Could not extract version from $PLUGIN_FILE${NC}"
    exit 1
fi

echo -e "${GREEN}Building $PLUGIN_SLUG version $VERSION${NC}"

# Clean previous builds
echo -e "${YELLOW}Cleaning previous builds...${NC}"
rm -rf "$BUILD_DIR"
mkdir -p "$RELEASE_DIR"

# Copy files using rsync (excludes are based on .distignore)
echo -e "${YELLOW}Copying plugin files...${NC}"
rsync -av --progress \
    --exclude='.git' \
    --exclude='.github' \
    --exclude='.gitignore' \
    --exclude='.gitattributes' \
    --exclude='.wp-env.json' \
    --exclude='.eslintrc.js' \
    --exclude='.editorconfig' \
    --exclude='CLAUDE.md' \
    --exclude='phpcs.xml*' \
    --exclude='phpunit.xml*' \
    --exclude='jest.config.js' \
    --exclude='playwright.config.js' \
    --exclude='node_modules' \
    --exclude='vendor' \
    --exclude='composer.json' \
    --exclude='composer.lock' \
    --exclude='package.json' \
    --exclude='package-lock.json' \
    --exclude='tests' \
    --exclude='coverage' \
    --exclude='.phpunit.result.cache' \
    --exclude='build' \
    --exclude='bin' \
    --exclude='dist' \
    --exclude='.distignore' \
    --exclude='.vscode' \
    --exclude='.idea' \
    --exclude='*.sublime-*' \
    --exclude='.DS_Store' \
    --exclude='Thumbs.db' \
    --exclude='docs' \
    . "$RELEASE_DIR/"

# Install production Composer dependencies
if [ -f "composer.json" ]; then
    echo -e "${YELLOW}Installing production Composer dependencies...${NC}"
    cd "$RELEASE_DIR"
    composer install --no-dev --optimize-autoloader --no-interaction

    # Remove composer files after install
    rm -f composer.json composer.lock

    cd -
fi

# Create ZIP file
echo -e "${YELLOW}Creating ZIP archive...${NC}"
cd "$BUILD_DIR"
ZIP_FILE="${PLUGIN_SLUG}-${VERSION}.zip"

if command -v zip &> /dev/null; then
    zip -r "$ZIP_FILE" "$PLUGIN_SLUG" -q
else
    echo -e "${RED}Error: zip command not found. Please install zip.${NC}"
    exit 1
fi

cd ..

# Calculate file size
FILE_SIZE=$(du -h "$BUILD_DIR/$ZIP_FILE" | cut -f1)

# Success message
echo ""
echo -e "${GREEN}════════════════════════════════════════${NC}"
echo -e "${GREEN}✓ Build complete!${NC}"
echo -e "${GREEN}════════════════════════════════════════${NC}"
echo -e "Plugin: ${PLUGIN_SLUG}"
echo -e "Version: ${VERSION}"
echo -e "File: ${BUILD_DIR}/${ZIP_FILE}"
echo -e "Size: ${FILE_SIZE}"
echo ""
echo -e "${YELLOW}Next steps:${NC}"
echo -e "1. Test the ZIP by installing it in WordPress"
echo -e "2. Create a git tag: git tag v${VERSION}"
echo -e "3. Push the tag: git push origin v${VERSION}"
echo -e "4. GitHub Actions will create a release automatically"
echo ""

# Optional: Verify contents
if [ "$1" == "--verify" ]; then
    echo -e "${YELLOW}ZIP contents:${NC}"
    unzip -l "$BUILD_DIR/$ZIP_FILE" | head -20
fi
