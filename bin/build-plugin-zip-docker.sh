#!/bin/bash
# Build WordPress plugin ZIP using Docker (100% containerized, zero host dependencies)
# Requires: Docker Desktop ONLY (no rsync, no bash utilities, no PHP, no Composer)
# Usage: bash bin/build-plugin-zip-docker.sh [--verify]

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
IMAGE_NAME="${PLUGIN_SLUG}-builder"

echo -e "${GREEN}Building plugin using 100% containerized Docker build...${NC}"

# Check if Docker is available
if ! command -v docker &> /dev/null; then
    echo -e "${RED}Error: Docker is not installed or not in PATH${NC}"
    echo -e "${YELLOW}Please install Docker Desktop from https://www.docker.com/products/docker-desktop${NC}"
    exit 1
fi

# Check if Docker daemon is running
if ! docker info &> /dev/null; then
    echo -e "${RED}Error: Docker daemon is not running${NC}"
    echo -e "${YELLOW}Please start Docker Desktop${NC}"
    exit 1
fi

# Get version from plugin file (on host, before Docker build)
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
echo -e "${YELLOW}All operations running in Docker containers...${NC}"

# Clean previous builds
echo -e "${YELLOW}Cleaning previous builds...${NC}"
rm -rf "$BUILD_DIR"
mkdir -p "$BUILD_DIR"

# Build the Docker image using Dockerfile.build
# This handles:
# 1. Copying files (using .dockerignore for exclusions)
# 2. Installing Composer production dependencies
# 3. Creating the ZIP file
echo -e "${YELLOW}Building Docker image (copying files, installing dependencies, creating ZIP)...${NC}"
if ! docker build -f Dockerfile.build -t "$IMAGE_NAME" . --quiet; then
    echo -e "${RED}Error: Docker build failed${NC}"
    exit 1
fi

# Create a temporary container from the image
echo -e "${YELLOW}Extracting ZIP file from container...${NC}"
docker create --name "${PLUGIN_SLUG}-temp" "$IMAGE_NAME" > /dev/null

# Copy the ZIP file from the container to the build directory
if ! docker cp "${PLUGIN_SLUG}-temp:/cta-highlights-${VERSION}.zip" "$BUILD_DIR/"; then
    echo -e "${RED}Error: Failed to extract ZIP from container${NC}"
    docker rm "${PLUGIN_SLUG}-temp" > /dev/null 2>&1
    exit 1
fi

# Clean up temporary container
docker rm "${PLUGIN_SLUG}-temp" > /dev/null

# Verify ZIP was created
if [ ! -f "$BUILD_DIR/${PLUGIN_SLUG}-${VERSION}.zip" ]; then
    echo -e "${RED}Error: ZIP file was not created${NC}"
    exit 1
fi

# Calculate file size (this runs on host but is just for display)
FILE_SIZE=$(du -h "$BUILD_DIR/${PLUGIN_SLUG}-${VERSION}.zip" 2>/dev/null | cut -f1)
if [ -z "$FILE_SIZE" ]; then
    FILE_SIZE="unknown"
fi

# Success message
echo ""
echo -e "${GREEN}════════════════════════════════════════${NC}"
echo -e "${GREEN}✓ Build complete!${NC}"
echo -e "${GREEN}════════════════════════════════════════${NC}"
echo -e "Plugin: ${PLUGIN_SLUG}"
echo -e "Version: ${VERSION}"
echo -e "File: ${BUILD_DIR}/${PLUGIN_SLUG}-${VERSION}.zip"
echo -e "Size: ${FILE_SIZE}"
echo -e "Built using: 100% containerized Docker build"
echo -e "Host dependencies: Docker only (no rsync, no PHP, no Composer)"
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
    docker run --rm \
        -v "$(pwd)/$BUILD_DIR:/build" \
        -w /build \
        alpine:latest \
        sh -c "apk add --no-cache unzip > /dev/null 2>&1 && unzip -l ${PLUGIN_SLUG}-${VERSION}.zip" | head -50
fi

# Optional: Clean up Docker image (uncomment if you want to save space)
# docker rmi "$IMAGE_NAME" > /dev/null 2>&1
