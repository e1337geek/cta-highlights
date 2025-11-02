#!/bin/bash
# Run PHPCS (PHP Code Sniffer) in Docker container (no local PHP required)
# Usage: bash bin/run-phpcs-docker.sh [phpcs-args]
#
# Examples:
#   bash bin/run-phpcs-docker.sh                     # Check all files
#   bash bin/run-phpcs-docker.sh includes/           # Check specific directory
#   bash bin/run-phpcs-docker.sh --report=summary    # Summary report

set -e

# Disable MSYS path conversion on Windows Git Bash
export MSYS_NO_PATHCONV=1

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}Running PHPCS in Docker container...${NC}"

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

# Check if vendor directory exists
if [ ! -d "vendor" ]; then
    echo -e "${YELLOW}vendor/ directory not found. Installing Composer dependencies...${NC}"
    bash bin/run-composer-docker.sh install
fi

# Get absolute path (handles Windows Git Bash path conversion)
WORK_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

# Run PHPCS in Docker container using local vendor/bin/phpcs
echo -e "${YELLOW}Checking code standards...${NC}"

docker run --rm \
    -v "${WORK_DIR}:/app" \
    -w /app \
    php:8.0-cli-alpine \
    vendor/bin/phpcs "$@"

EXIT_CODE=$?

if [ $EXIT_CODE -eq 0 ]; then
    echo -e "${GREEN}✓ No coding standard violations found!${NC}"
else
    echo -e "${YELLOW}Run 'npm run lint:fix:docker' to auto-fix some issues${NC}"
fi

exit $EXIT_CODE
