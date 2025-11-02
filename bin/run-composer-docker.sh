#!/bin/bash
# Run Composer in Docker container (no local Composer required)
# Usage: bash bin/run-composer-docker.sh <composer-command> [args]
#
# Examples:
#   bash bin/run-composer-docker.sh install              # Install dependencies
#   bash bin/run-composer-docker.sh update               # Update dependencies
#   bash bin/run-composer-docker.sh require pkg/name     # Add dependency
#   bash bin/run-composer-docker.sh dump-autoload        # Regenerate autoloader

set -e

# Disable MSYS path conversion on Windows Git Bash
export MSYS_NO_PATHCONV=1

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}Running Composer in Docker container...${NC}"

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

# Default to 'install' if no command provided
COMPOSER_COMMAND="${1:-install}"
shift 2>/dev/null || true

echo -e "${YELLOW}Executing: composer $COMPOSER_COMMAND $@${NC}"

# Get absolute path (handles Windows Git Bash path conversion)
WORK_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

# Run Composer in official Composer Docker image
docker run --rm \
    -v "${WORK_DIR}:/app" \
    -w /app \
    composer:2 \
    sh -c "git config --global --add safe.directory /app 2>/dev/null || true; composer $COMPOSER_COMMAND $*"

EXIT_CODE=$?

if [ $EXIT_CODE -eq 0 ]; then
    echo -e "${GREEN}✓ Composer command completed successfully${NC}"

    # Show helpful message after install
    if [ "$COMPOSER_COMMAND" = "install" ]; then
        echo -e "${YELLOW}Dependencies installed in vendor/${NC}"
        echo -e "${YELLOW}You can now run tests with: npm run test:php:docker${NC}"
    fi
else
    echo -e "${RED}✗ Composer command failed${NC}"
fi

exit $EXIT_CODE
