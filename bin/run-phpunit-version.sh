#!/bin/bash
#
# Run PHPUnit with Specific PHP Version
#
# This script runs PHPUnit tests in a Docker container with a specific PHP version.
# It's designed to help debug CI failures locally by testing against the same PHP
# versions used in GitHub Actions.
#
# The script:
# 1. Ensures wp-env is running (for MySQL database)
# 2. Finds the wp-env Docker network
# 3. Runs tests in a container with the specified PHP version
# 4. Connects to wp-env's MySQL database
#
# Usage:
#   bash bin/run-phpunit-version.sh <php-version> [phpunit-args]
#   npm run test:php:7.4
#   npm run test:php:8.1
#   npm run test:php:8.2
#
# Examples:
#   bash bin/run-phpunit-version.sh 7.4
#   bash bin/run-phpunit-version.sh 8.1 --testsuite=unit
#   bash bin/run-phpunit-version.sh 8.2 --filter=TemplateRegistryTest
#

set -e

# Check if PHP version is provided
if [ -z "$1" ]; then
    echo "Error: PHP version not specified"
    echo "Usage: bash bin/run-phpunit-version.sh <php-version> [phpunit-args]"
    echo "Example: bash bin/run-phpunit-version.sh 7.4"
    exit 1
fi

PHP_VERSION="$1"
shift  # Remove first argument, leaving PHPUnit args

# Validate PHP version
case "$PHP_VERSION" in
    7.4|8.0|8.1|8.2)
        # Valid versions
        ;;
    *)
        echo "Error: Unsupported PHP version: $PHP_VERSION"
        echo "Supported versions: 7.4, 8.0, 8.1, 8.2"
        exit 1
        ;;
esac

echo "Running PHPUnit tests with PHP $PHP_VERSION..."

# Check if wp-env is running by checking if port 8889 (tests) is listening
if ! nc -z localhost 8889 2>/dev/null && ! curl -s http://localhost:8889 > /dev/null 2>&1; then
    echo "wp-env is not running. Starting it now..."
    npm run env:start
    echo "Waiting for wp-env to be ready..."
    sleep 5
fi

# Get the directory of this script
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PLUGIN_DIR="$(dirname "$SCRIPT_DIR")"

# Convert Windows paths to Unix paths if on Windows (Git Bash)
if [[ "$OSTYPE" == "msys" ]] || [[ "$OSTYPE" == "win32" ]]; then
    # Convert Windows path to Unix path for Docker
    WORK_DIR=$(echo "$PLUGIN_DIR" | sed 's|\\|/|g' | sed 's|^\([A-Za-z]\):|/\1|')
else
    WORK_DIR="$PLUGIN_DIR"
fi

# Find wp-env network
echo "Detecting wp-env Docker network..."
WP_ENV_NETWORK=$(docker network ls --format '{{.Name}}' | grep 'wp-env' | head -1)

if [ -z "$WP_ENV_NETWORK" ]; then
    echo "Error: Could not find wp-env Docker network"
    echo "Make sure wp-env is running: npm run env:start"
    exit 1
fi

echo "Found wp-env network: $WP_ENV_NETWORK"

# Database configuration (unique per PHP version to avoid conflicts)
DB_NAME="wordpress_test_php${PHP_VERSION}"
DB_USER="root"
DB_PASSWORD="password"
DB_HOST="tests-mysql"  # wp-env's test MySQL container name

# WordPress test library directory
WP_TESTS_DIR="/tmp/wordpress-tests-lib"

echo "Running tests in Docker container (PHP $PHP_VERSION)..."
echo "Database: $DB_NAME on $DB_HOST"

# Build PHPUnit command
PHPUNIT_CMD="vendor/bin/phpunit"
if [ $# -gt 0 ]; then
    PHPUNIT_CMD="$PHPUNIT_CMD $@"
fi

# Run tests in Docker container
# This mirrors the CI approach: install deps + WordPress test lib + run tests in one container
docker run --rm \
    -v "${WORK_DIR}:/app" \
    -w /app \
    --network "$WP_ENV_NETWORK" \
    -e WP_TESTS_DB_HOST="$DB_HOST" \
    -e WP_TESTS_DB_NAME="$DB_NAME" \
    -e WP_TESTS_DB_USER="$DB_USER" \
    -e WP_TESTS_DB_PASSWORD="$DB_PASSWORD" \
    "php:${PHP_VERSION}-cli-alpine" \
    sh -c "
        set -e

        echo '===> Installing system dependencies...'
        apk add --no-cache bash subversion curl tar rsync mysql-client > /dev/null 2>&1

        echo '===> Checking database connection...'
        until mysqladmin ping -h\"\$WP_TESTS_DB_HOST\" -u\"\$WP_TESTS_DB_USER\" -p\"\$WP_TESTS_DB_PASSWORD\" --silent 2>/dev/null; do
            echo 'Waiting for MySQL...'
            sleep 2
        done

        echo '===> Installing WordPress test library...'
        bash tests/bin/install-wp-tests.sh \"\$WP_TESTS_DB_NAME\" \"\$WP_TESTS_DB_USER\" \"\$WP_TESTS_DB_PASSWORD\" \"\$WP_TESTS_DB_HOST\" latest true

        echo '===> Installing PHP mysqli extension...'
        docker-php-ext-install mysqli > /dev/null 2>&1

        echo '===> Running PHPUnit tests...'
        $PHPUNIT_CMD
    "

echo ""
echo "Tests completed successfully with PHP $PHP_VERSION!"
