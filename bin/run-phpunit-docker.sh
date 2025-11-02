#!/bin/bash
# Run PHPUnit tests in Docker container (no local PHP required)
# Usage: bash bin/run-phpunit-docker.sh [phpunit-args]
#
# Examples:
#   bash bin/run-phpunit-docker.sh                    # Run all tests
#   bash bin/run-phpunit-docker.sh --testsuite unit   # Run unit tests only
#   bash bin/run-phpunit-docker.sh --filter testFoo   # Run specific test

set -e

# Disable MSYS path conversion on Windows Git Bash
export MSYS_NO_PATHCONV=1

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}Running PHPUnit in Docker container...${NC}"

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

# Check if wp-env is running (needed for integration tests with MySQL)
WP_ENV_RUNNING=false
if curl -s http://localhost:8889 > /dev/null 2>&1; then
    WP_ENV_RUNNING=true
    echo -e "${GREEN}wp-env is running (MySQL available for integration tests)${NC}"
else
    echo -e "${YELLOW}wp-env is not running. Starting it for database access...${NC}"
    npm run env:start > /dev/null 2>&1
    echo -e "${GREEN}wp-env started${NC}"
    WP_ENV_RUNNING=true
fi

# Get the wp-env network name
WP_ENV_NETWORK=$(docker network ls --format '{{.Name}}' | grep wp-env | head -1)

if [ -z "$WP_ENV_NETWORK" ]; then
    echo -e "${YELLOW}Warning: wp-env network not found. Integration tests may fail.${NC}"
    NETWORK_ARG=""
else
    NETWORK_ARG="--network $WP_ENV_NETWORK"
    echo -e "${GREEN}Connected to wp-env network: $WP_ENV_NETWORK${NC}"
fi

# Get absolute path (handles Windows Git Bash path conversion)
WORK_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

# Run PHPUnit in Docker container
echo -e "${YELLOW}Executing PHPUnit tests...${NC}"

docker run --rm \
    -v "${WORK_DIR}:/app" \
    -w /app \
    $NETWORK_ARG \
    -e WP_TESTS_DB_HOST=mysql \
    -e WP_TESTS_DB_NAME=wordpress_test \
    -e WP_TESTS_DB_USER=root \
    -e WP_TESTS_DB_PASSWORD=password \
    php:8.0-cli-alpine \
    vendor/bin/phpunit "$@"

EXIT_CODE=$?

if [ $EXIT_CODE -eq 0 ]; then
    echo -e "${GREEN}✓ All tests passed!${NC}"
else
    echo -e "${RED}✗ Some tests failed${NC}"
fi

exit $EXIT_CODE
