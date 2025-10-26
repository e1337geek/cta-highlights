#!/usr/bin/env bash

# Run PHPUnit Unit Tests
#
# Quick script to run only unit tests (no integration tests).
# Useful for fast iteration during development.

set -e

echo "Running PHPUnit unit tests..."
echo ""

vendor/bin/phpunit --testsuite unit --colors=always

echo ""
echo "✓ Unit tests completed!"
