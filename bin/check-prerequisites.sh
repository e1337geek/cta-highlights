#!/bin/bash
# Check development environment prerequisites
# Usage: bash bin/check-prerequisites.sh

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}═══════════════════════════════════════════════════${NC}"
echo -e "${BLUE}  CTA Highlights - Development Prerequisites Check${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════${NC}"
echo ""

# Track if all requirements are met
ALL_MET=true

# Function to check a requirement
check_requirement() {
    local name=$1
    local command=$2
    local required=$3
    local install_url=$4

    if command -v $command &> /dev/null; then
        local version=$($command --version 2>&1 | head -1)
        echo -e "${GREEN}✓${NC} $name: ${GREEN}Installed${NC}"
        echo -e "  ${version}"
    else
        if [ "$required" = "true" ]; then
            echo -e "${RED}✗${NC} $name: ${RED}NOT FOUND (REQUIRED)${NC}"
            echo -e "  Install: ${install_url}"
            ALL_MET=false
        else
            echo -e "${YELLOW}○${NC} $name: ${YELLOW}Not installed (optional)${NC}"
            echo -e "  Install if needed: ${install_url}"
        fi
    fi
    echo ""
}

# Check Docker (REQUIRED)
echo -e "${BLUE}Required Dependencies:${NC}"
echo ""
check_requirement \
    "Docker" \
    "docker" \
    "true" \
    "https://www.docker.com/products/docker-desktop"

# Check Docker daemon status
if command -v docker &> /dev/null; then
    if docker info &> /dev/null 2>&1; then
        echo -e "${GREEN}✓${NC} Docker daemon: ${GREEN}Running${NC}"
    else
        echo -e "${RED}✗${NC} Docker daemon: ${RED}NOT RUNNING${NC}"
        echo -e "  Please start Docker Desktop"
        ALL_MET=false
    fi
    echo ""
fi

# Check Node.js (REQUIRED)
check_requirement \
    "Node.js" \
    "node" \
    "true" \
    "https://nodejs.org/"

# Check npm (REQUIRED - comes with Node.js)
check_requirement \
    "npm" \
    "npm" \
    "true" \
    "https://nodejs.org/"

# Check Git (REQUIRED)
check_requirement \
    "Git" \
    "git" \
    "true" \
    "https://git-scm.com/downloads"

echo -e "${BLUE}Optional Dependencies (for local development):${NC}"
echo ""

# Check PHP (OPTIONAL - can use Docker)
check_requirement \
    "PHP" \
    "php" \
    "false" \
    "https://windows.php.net/download/"

# Check Composer (OPTIONAL - can use Docker)
check_requirement \
    "Composer" \
    "composer" \
    "false" \
    "https://getcomposer.org/"

# Summary
echo -e "${BLUE}═══════════════════════════════════════════════════${NC}"
if [ "$ALL_MET" = true ]; then
    echo -e "${GREEN}✓ All required prerequisites are met!${NC}"
    echo ""
    echo -e "${YELLOW}Next steps:${NC}"
    echo -e "  1. npm run setup           # Install dependencies"
    echo -e "  2. npm run env:start       # Start WordPress environment"
    echo -e "  3. npm run test:php:docker # Run PHP tests (no local PHP needed)"
    echo -e "  4. npm run build:zip       # Build production ZIP"
else
    echo -e "${RED}✗ Some required prerequisites are missing${NC}"
    echo ""
    echo -e "${YELLOW}Please install the missing requirements above${NC}"
    exit 1
fi
echo -e "${BLUE}═══════════════════════════════════════════════════${NC}"
echo ""

# Show workflow information
echo -e "${BLUE}Development Workflow:${NC}"
echo ""
echo -e "${GREEN}Containerized (no PHP/Composer needed):${NC}"
echo -e "  npm run composer:install       # Install PHP dependencies in container"
echo -e "  npm run test:php:docker        # Run PHP tests in container"
echo -e "  npm run lint:php:docker        # Run PHP linting in container"
echo -e "  npm run build:zip              # Build plugin ZIP in container"
echo ""
if command -v php &> /dev/null && command -v composer &> /dev/null; then
    echo -e "${GREEN}Local (using your installed PHP/Composer):${NC}"
    echo -e "  composer install               # Install PHP dependencies locally"
    echo -e "  npm run test:php               # Run PHP tests locally"
    echo -e "  npm run lint:php               # Run PHP linting locally"
    echo ""
fi
echo -e "${BLUE}See DEVELOPMENT-SETUP.md for detailed setup instructions${NC}"
echo ""
