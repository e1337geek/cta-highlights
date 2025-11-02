#!/bin/bash
#
# Developer Environment Setup Script
#
# This script sets up the complete development environment for the CTA Highlights plugin.
# It checks all prerequisites, installs dependencies, and verifies the setup.
#
# Requirements:
# - Docker Desktop (running)
# - Node.js 16+
# - npm 8+
#
# Usage: npm run setup:dev
#

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Helper functions
print_header() {
    echo -e "\n${BLUE}========================================${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}========================================${NC}\n"
}

print_success() {
    echo -e "${GREEN}✓${NC} $1"
}

print_error() {
    echo -e "${RED}✗${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}⚠${NC} $1"
}

print_info() {
    echo -e "${BLUE}ℹ${NC} $1"
}

# Check prerequisites
check_prerequisites() {
    print_header "Checking Prerequisites"

    local all_good=true

    # Check Docker
    if command -v docker &> /dev/null; then
        if docker info &> /dev/null; then
            print_success "Docker is installed and running"
        else
            print_error "Docker is installed but not running"
            print_info "Please start Docker Desktop and try again"
            all_good=false
        fi
    else
        print_error "Docker is not installed"
        print_info "Please install Docker Desktop from https://www.docker.com/products/docker-desktop"
        all_good=false
    fi

    # Check Node.js
    if command -v node &> /dev/null; then
        NODE_VERSION=$(node --version | cut -d'v' -f2 | cut -d'.' -f1)
        if [ "$NODE_VERSION" -ge 16 ]; then
            print_success "Node.js $(node --version) is installed"
        else
            print_error "Node.js version is $(node --version), but 16+ is required"
            all_good=false
        fi
    else
        print_error "Node.js is not installed"
        print_info "Please install Node.js 16+ from https://nodejs.org"
        all_good=false
    fi

    # Check npm
    if command -v npm &> /dev/null; then
        NPM_VERSION=$(npm --version | cut -d'.' -f1)
        if [ "$NPM_VERSION" -ge 8 ]; then
            print_success "npm $(npm --version) is installed"
        else
            print_error "npm version is $(npm --version), but 8+ is required"
            all_good=false
        fi
    else
        print_error "npm is not installed"
        all_good=false
    fi

    if [ "$all_good" = false ]; then
        echo ""
        print_error "Prerequisites check failed. Please install missing requirements and try again."
        exit 1
    fi
}

# Install npm dependencies
install_npm_dependencies() {
    print_header "Installing npm Dependencies"

    if [ -f "package-lock.json" ]; then
        print_info "Running npm ci (clean install)..."
        npm ci
    else
        print_info "Running npm install..."
        npm install
    fi

    print_success "npm dependencies installed"
}

# Start wp-env
start_wp_env() {
    print_header "Starting WordPress Environment (wp-env)"

    print_info "Starting wp-env (this may take a few minutes on first run)..."
    npm run env:start

    # Wait for WordPress to be ready
    print_info "Waiting for WordPress to be ready..."
    sleep 5

    # Check if WordPress is responding
    max_attempts=30
    attempt=0
    until curl -s http://localhost:8888 > /dev/null || [ $attempt -eq $max_attempts ]; do
        sleep 2
        ((attempt++))
        echo -n "."
    done
    echo ""

    if [ $attempt -eq $max_attempts ]; then
        print_warning "WordPress may not be fully ready yet, but continuing..."
    else
        print_success "wp-env started successfully"
    fi
}

# Install Composer dependencies
install_composer_dependencies() {
    print_header "Installing PHP Dependencies (Composer)"

    print_info "Installing Composer dependencies inside wp-env..."
    npm run composer:install

    print_success "Composer dependencies installed"
}

# Install WordPress test library
install_wordpress_test_library() {
    print_header "Installing WordPress Test Library"

    print_info "Checking WordPress test library availability..."

    # wp-env includes PHPUnit and WordPress test library by default
    # We just need to verify it's accessible
    if wp-env run tests-cli test -d /wordpress-phpunit &> /dev/null; then
        print_success "WordPress test library is available in wp-env"
    else
        print_warning "WordPress test library not found, but wp-env should provide it automatically"
    fi
}

# Verify setup
verify_setup() {
    print_header "Verifying Setup"

    print_info "Running a quick test to verify everything works..."

    # Try to run a simple wp-cli command
    if wp-env run cli wp --version &> /dev/null; then
        print_success "wp-env CLI is working"
    else
        print_warning "wp-env CLI check failed (may not be critical)"
    fi

    # Check if vendor directory exists
    if wp-env run cli --env-cwd=wp-content/plugins/cta-highlights test -d vendor &> /dev/null; then
        print_success "PHP dependencies are installed"
    else
        print_error "PHP dependencies not found"
    fi

    print_success "Setup verification complete!"
}

# Print success message
print_success_message() {
    print_header "Setup Complete!"

    echo -e "${GREEN}Your development environment is ready!${NC}\n"

    echo "WordPress Sites:"
    echo "  Development: http://localhost:8888"
    echo "  Test:        http://localhost:8889"
    echo "  Admin:       http://localhost:8888/wp-admin"
    echo "  Username:    admin"
    echo "  Password:    password"
    echo ""

    echo "Available Commands:"
    echo "  ${BLUE}npm run env:start${NC}         - Start wp-env"
    echo "  ${BLUE}npm run env:stop${NC}          - Stop wp-env"
    echo "  ${BLUE}npm run test:php${NC}          - Run PHP tests"
    echo "  ${BLUE}npm run test:php:unit${NC}     - Run PHP unit tests"
    echo "  ${BLUE}npm run test:js${NC}           - Run JavaScript tests"
    echo "  ${BLUE}npm run test:e2e${NC}          - Run E2E tests"
    echo "  ${BLUE}npm run test:all${NC}          - Run all tests"
    echo "  ${BLUE}npm run lint${NC}              - Run linting"
    echo "  ${BLUE}npm run lint:fix${NC}          - Fix linting issues"
    echo ""

    echo "Multi-Version PHP Testing (CI debugging):"
    echo "  ${BLUE}npm run test:php:7.4${NC}      - Test with PHP 7.4"
    echo "  ${BLUE}npm run test:php:8.1${NC}      - Test with PHP 8.1"
    echo "  ${BLUE}npm run test:php:8.2${NC}      - Test with PHP 8.2"
    echo ""

    echo "Documentation:"
    echo "  DEVELOPMENT-SETUP.md   - Development setup guide"
    echo "  TESTING-GUIDE.md       - Testing documentation"
    echo "  CONTRIBUTING.md        - Contribution guidelines"
    echo ""

    print_info "To get started, try running: ${BLUE}npm run test:php:unit${NC}"
}

# Main execution
main() {
    echo -e "${GREEN}"
    echo "╔═══════════════════════════════════════════════════════════╗"
    echo "║                                                           ║"
    echo "║      CTA Highlights Plugin - Development Setup           ║"
    echo "║                                                           ║"
    echo "╚═══════════════════════════════════════════════════════════╝"
    echo -e "${NC}"

    check_prerequisites
    install_npm_dependencies
    start_wp_env
    install_composer_dependencies
    install_wordpress_test_library
    verify_setup
    print_success_message
}

# Run main function
main
