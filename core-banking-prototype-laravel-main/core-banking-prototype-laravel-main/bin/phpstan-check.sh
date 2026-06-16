#!/bin/bash

# PHPStan Check Script
# This script runs PHPStan analysis and helps maintain code quality

echo "=== PHPStan Analysis ==="
echo

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Run PHPStan
echo "Running PHPStan analysis..."
./vendor/bin/phpstan analyse --memory-limit=2G

# Check exit code
if [ $? -eq 0 ]; then
    echo -e "\n${GREEN}✓ PHPStan analysis passed with 0 errors!${NC}"
else
    echo -e "\n${RED}✗ PHPStan analysis failed${NC}"
    echo -e "${YELLOW}To update the baseline with new errors, run:${NC}"
    echo "  ./vendor/bin/phpstan analyse --generate-baseline"
    exit 1
fi

# Check for outdated baseline entries
echo -e "\nChecking for outdated baseline entries..."
./vendor/bin/phpstan analyse --memory-limit=2G --error-format=json 2>/dev/null | jq -r '.totals.errors' > /tmp/phpstan-errors.tmp

if [ -f /tmp/phpstan-errors.tmp ]; then
    ERRORS=$(cat /tmp/phpstan-errors.tmp)
    if [ "$ERRORS" = "0" ]; then
        echo -e "${GREEN}✓ No outdated baseline entries found${NC}"
    else
        echo -e "${YELLOW}! Found outdated baseline entries. Consider regenerating the baseline.${NC}"
    fi
    rm /tmp/phpstan-errors.tmp
fi

echo -e "\n=== PHPStan Check Complete ==="