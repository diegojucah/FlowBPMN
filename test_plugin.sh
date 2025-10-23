#!/bin/bash

# -------------------------------------------------------------------------
# flowBPMN Plugin - Test Script
# -------------------------------------------------------------------------
# Quick test script to verify plugin installation in Docker
# Usage: ./test_plugin.sh
# -------------------------------------------------------------------------

set -e

echo "============================================"
echo "   flowBPMN Plugin - Installation Test"
echo "============================================"
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Test 1: Check if we're in GLPI directory
echo "Test 1: Checking GLPI installation..."
if [ -f "bin/console" ]; then
    echo -e "${GREEN}✓${NC} GLPI detected"
else
    echo -e "${RED}✗${NC} Not in GLPI directory. Run from /var/www/glpi"
    exit 1
fi

# Test 2: Check if plugin directory exists
echo ""
echo "Test 2: Checking plugin files..."
if [ -d "plugins/flowBPMN" ]; then
    echo -e "${GREEN}✓${NC} Plugin directory found"
else
    echo -e "${RED}✗${NC} Plugin directory not found at plugins/flowBPMN"
    exit 1
fi

# Test 3: Check setup.php
if [ -f "plugins/flowBPMN/setup.php" ]; then
    echo -e "${GREEN}✓${NC} setup.php found"
else
    echo -e "${RED}✗${NC} setup.php not found"
    exit 1
fi

# Test 4: List plugins
echo ""
echo "Test 3: Listing GLPI plugins..."
php bin/console glpi:plugin:list | grep -i flowbpmn || echo -e "${YELLOW}⚠${NC} Plugin not yet installed"

# Test 5: Check PHP version
echo ""
echo "Test 4: Checking PHP version..."
PHP_VERSION=$(php -r 'echo PHP_VERSION;')
echo "PHP Version: $PHP_VERSION"

if php -r 'exit(version_compare(PHP_VERSION, "8.0.0", ">=") ? 0 : 1);'; then
    echo -e "${GREEN}✓${NC} PHP version is adequate (>= 8.0)"
else
    echo -e "${RED}✗${NC} PHP version too old. Requires 8.0+"
    exit 1
fi

# Test 6: Check database connection
echo ""
echo "Test 5: Checking database connection..."
if php -r '
    include "inc/includes.php";
    $DB = new DBmysql();
    if ($DB->connected) {
        echo "connected";
        exit(0);
    } else {
        exit(1);
    }
' 2>/dev/null | grep -q "connected"; then
    echo -e "${GREEN}✓${NC} Database connection OK"
else
    echo -e "${RED}✗${NC} Database connection failed"
    exit 1
fi

echo ""
echo "============================================"
echo "   All basic tests passed! ✓"
echo "============================================"
echo ""
echo "Next steps:"
echo "1. Install: php bin/console glpi:plugin:install flowbpmn"
echo "2. Activate: php bin/console glpi:plugin:activate flowbpmn"
echo ""
echo "Or install via web interface:"
echo "→ Setup → Plugins → BPMN Flow → Install → Activate"
echo ""
