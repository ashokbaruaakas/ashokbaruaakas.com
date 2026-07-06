#!/bin/bash
set -euo pipefail

echo "Starting execution of Post-Deployment script..."

php artisan migrate --force

php artisan optimize:clear

php artisan optimize

echo "Post-Deployment script execution completed successfully."
