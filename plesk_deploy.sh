#!/bin/bash

# =============================================================================
# SNS Furniture - Plesk Deployment & Optimization Script
# =============================================================================
# Run this script whenever you push new code to your Plesk server.
# It will optimize Composer, cache Laravel configurations, and ensure 
# everything runs as fast as possible in production.
# =============================================================================

echo "🚀 Starting Deployment Optimization..."

# 1. Optimize Composer Autoloader
echo "📦 Optimizing Composer autoloader..."
composer install --optimize-autoloader --no-dev

# 2. Clear out any old caches that might conflict
echo "🧹 Clearing old caches..."
php artisan cache:clear
php artisan clear-compiled

# 3. Cache Configuration (Massive performance boost)
echo "⚙️ Caching configuration..."
php artisan config:cache

# 4. Cache Routes
echo "🛣️ Caching routes..."
php artisan route:cache

# 5. Cache Views
echo "🖼️ Caching Blade views..."
php artisan view:cache

# 6. Cache Events
echo "📡 Caching events..."
php artisan event:cache

# (Optional) Ensure storage directory has proper permissions
# chmod -R 775 storage bootstrap/cache

echo "✅ Deployment optimizations completed successfully!"
