#!/bin/bash
# Run PHPCBF (PHP Code Beautifier and Fixer) in Docker container (no local PHP required)
# Usage: bash bin/run-phpcbf-docker.sh [phpcbf-args]
#
# Examples:
#   bash bin/run-phpcbf-docker.sh                    # Fix all files
#   bash bin/run-phpcbf-docker.sh includes/          # Fix specific directory

set -e

# Disable MSYS path conversion on Windows Git Bash
export MSYS_NO_PATHCONV=1

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}Running PHPCBF in Docker container...${NC}"

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

# Run PHPCBF in Docker container using local vendor/bin/phpcbf
echo -e "${YELLOW}Auto-fixing coding standard violations...${NC}"

docker run --rm \
    -v "${WORK_DIR}:/app" \
    -w /app \
    php:8.0-cli-alpine \
    vendor/bin/phpcbf "$@"

EXIT_CODE=$?

if [ $EXIT_CODE -eq 0 ] || [ $EXIT_CODE -eq 1 ]; then
    # Exit code 0 = no fixable errors found
    # Exit code 1 = fixed some errors
    echo -e "${GREEN}✓ Code formatting complete${NC}"
    echo -e "${YELLOW}Run 'npm run lint:php:docker' to check for remaining issues${NC}"
    exit 0
else
    echo -e "${RED}✗ PHPCBF encountered an error${NC}"
    exit $EXIT_CODE
fi
