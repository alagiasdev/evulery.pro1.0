#!/bin/bash
# ============================================================
# Evulery.Pro - Deploy Script
# Eseguire sul server dopo il primo setup.
# Uso: bash deploy.sh
# ============================================================

set -e

echo "=== Evulery.Pro Deploy ==="
echo ""

# Pull latest code
echo "[1/4] Pulling latest code from GitHub..."
git pull origin main

# Install/update dependencies
echo "[2/4] Installing dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

# Fix permissions
echo "[3/4] Setting permissions..."
chmod -R 755 storage/
chmod -R 755 public/uploads/

# Clear caches
echo "[4/4] Clearing caches..."
rm -f storage/cache/*

echo ""
echo "=== Deploy completato! ==="
echo "Verifica: apri il sito nel browser e controlla che tutto funzioni."
