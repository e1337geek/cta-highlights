#!/usr/bin/env bash

# Run Playwright E2E Tests
#
# Quick script to run end-to-end tests.

set -e

echo "Running Playwright E2E tests..."
echo ""

# Check if wp-env is running
if ! curl -s http://localhost:8888 > /dev/null; then
    echo "WordPress environment is not running."
    echo "Starting wp-env..."
    npm run env:start
fi

npm run test:e2e

echo ""
echo "✓ E2E tests completed!"
