#!/usr/bin/env bash

# Run Jest JavaScript Tests
#
# Quick script to run JavaScript tests.

set -e

echo "Running Jest JavaScript tests..."
echo ""

npm run test:js

echo ""
echo "✓ JavaScript tests completed!"
