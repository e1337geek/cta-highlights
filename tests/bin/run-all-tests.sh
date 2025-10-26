#!/usr/bin/env bash

# Run All Tests
#
# Comprehensive test script that runs:
# 1. PHPUnit unit tests
# 2. PHPUnit integration tests
# 3. Jest JavaScript tests
# 4. Playwright E2E tests

set -e

echo "========================================="
echo " Running Complete Test Suite"
echo "========================================="
echo ""

# Colors for output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# 1. PHPUnit Unit Tests
echo -e "${BLUE}[1/4] Running PHPUnit unit tests...${NC}"
vendor/bin/phpunit --testsuite unit --colors=always
echo -e "${GREEN}✓ Unit tests passed!${NC}"
echo ""

# 2. PHPUnit Integration Tests
echo -e "${BLUE}[2/4] Running PHPUnit integration tests...${NC}"
vendor/bin/phpunit --testsuite integration --colors=always
echo -e "${GREEN}✓ Integration tests passed!${NC}"
echo ""

# 3. Jest JavaScript Tests
echo -e "${BLUE}[3/4] Running Jest JavaScript tests...${NC}"
npm run test:js
echo -e "${GREEN}✓ JavaScript tests passed!${NC}"
echo ""

# 4. Playwright E2E Tests
echo -e "${BLUE}[4/4] Running Playwright E2E tests...${NC}"

# Check if wp-env is running
if ! curl -s http://localhost:8888 > /dev/null; then
    echo "WordPress environment is not running. Starting wp-env..."
    npm run env:start
fi

npm run test:e2e
echo -e "${GREEN}✓ E2E tests passed!${NC}"
echo ""

echo "========================================="
echo -e "${GREEN} All Tests Passed! ✓${NC}"
echo "========================================="
echo ""
echo "Test Summary:"
echo "  • PHPUnit Unit Tests: PASSED"
echo "  • PHPUnit Integration Tests: PASSED"
echo "  • Jest JavaScript Tests: PASSED"
echo "  • Playwright E2E Tests: PASSED"
echo ""
