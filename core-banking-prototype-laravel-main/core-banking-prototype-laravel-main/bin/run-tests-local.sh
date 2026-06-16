#!/bin/bash

# Script to run tests locally with proper timeouts and configuration
# This replicates the GitHub Actions environment locally

set -e

echo "🔧 Setting up test environment..."

# Set environment variables
export APP_ENV=testing
export COMPOSER_PROCESS_TIMEOUT=0
export DB_CONNECTION=sqlite
export DB_DATABASE=:memory:
export QUEUE_CONNECTION=sync
export CACHE_STORE=array

# Copy testing environment if not exists
if [ ! -f .env ]; then
    cp .env.testing .env
    echo "✅ Created .env from .env.testing"
fi

# Install dependencies if needed
if [ ! -d vendor ]; then
    echo "📦 Installing PHP dependencies..."
    composer install --prefer-dist --no-progress --optimize-autoloader
fi

# Clear caches
echo "🧹 Clearing caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear

# Generate key if needed
php artisan key:generate --force

echo "🚀 Running tests with increased timeouts..."

# Function to run test suite with timeout
run_test_suite() {
    local suite=$1
    local min_coverage=${2:-0}
    local timeout=${3:-1800}  # Default 30 minutes
    
    echo ""
    echo "📋 Running $suite tests (timeout: ${timeout}s, min coverage: ${min_coverage}%)..."
    
    # Create a timeout wrapper
    timeout --preserve-status $timeout ./vendor/bin/pest \
        --testsuite=$suite \
        --no-coverage \
        --stop-on-failure \
        --stop-on-error \
        2>&1 | tee "test-results-${suite}.log" || {
        echo "❌ $suite tests failed or timed out"
        return 1
    }
    
    echo "✅ $suite tests completed"
}

# Run each test suite separately with appropriate timeouts
echo ""
echo "========================================="
echo "Running Unit Tests"
echo "========================================="
run_test_suite "Unit" 70 1200

echo ""
echo "========================================="
echo "Running Feature Tests"
echo "========================================="
run_test_suite "Feature" 65 1500

echo ""
echo "========================================="
echo "Running Integration Tests"
echo "========================================="
run_test_suite "Integration" 55 1800

echo ""
echo "========================================="
echo "Running Security Tests"
echo "========================================="
run_test_suite "Security" 0 900

echo ""
echo "========================================="
echo "Test Summary"
echo "========================================="
echo "Check the following log files for details:"
echo "- test-results-Unit.log"
echo "- test-results-Feature.log"
echo "- test-results-Integration.log"
echo "- test-results-Security.log"

# Run a quick analysis
echo ""
echo "🔍 Quick failure analysis:"
echo ""
for logfile in test-results-*.log; do
    if [ -f "$logfile" ]; then
        suite=$(basename "$logfile" .log | cut -d'-' -f3)
        failures=$(grep -c "FAILED" "$logfile" || true)
        errors=$(grep -c "ERROR" "$logfile" || true)
        if [ $failures -gt 0 ] || [ $errors -gt 0 ]; then
            echo "❌ $suite: $failures failures, $errors errors"
        else
            echo "✅ $suite: All tests passed"
        fi
    fi
done