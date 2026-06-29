#!/bin/bash
set -e

echo "Starting execution of Post-Deployment script..."

php artisan storage:link --force

php artisan migrate --force

php artisan optimize

echo "Post-Deployment script execution completed successfully."
