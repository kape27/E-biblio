#!/bin/bash
set -e

echo "🚀 Starting E-Lib on Render..."

# Wait for database to be ready
echo "⏳ Waiting for database connection..."
MAX_RETRIES=30
RETRY_COUNT=0

until php -r "
try {
    \$pdo = new PDO(
        'mysql:host=' . getenv('DB_HOST') . ';dbname=' . getenv('DB_NAME'),
        getenv('DB_USER'),
        getenv('DB_PASS'),
        [PDO::ATTR_TIMEOUT => 5]
    );
    echo '✅ Database connection successful' . PHP_EOL;
    exit(0);
} catch (PDOException \$e) {
    echo '⚠️  Database not ready: ' . \$e->getMessage() . PHP_EOL;
    exit(1);
}
" || [ $RETRY_COUNT -eq $MAX_RETRIES ]; do
    RETRY_COUNT=$((RETRY_COUNT + 1))
    echo "Retry $RETRY_COUNT/$MAX_RETRIES..."
    sleep 3
done

if [ $RETRY_COUNT -eq $MAX_RETRIES ]; then
    echo "❌ Database connection failed after $MAX_RETRIES attempts"
    exit 1
fi

echo "✅ Database is ready!"

# Run database setup/migrations
echo "🔧 Running database setup..."
php /var/www/html/admin/setup.php || echo "⚠️  Setup script completed with warnings"

# Set proper permissions for persistent disk
if [ -d "/var/www/html/uploads" ]; then
    echo "📁 Setting permissions for uploads directory..."
    chown -R www-data:www-data /var/www/html/uploads
    chmod -R 777 /var/www/html/uploads
fi

# Set permissions for logs
chown -R www-data:www-data /var/www/html/logs
chmod -R 777 /var/www/html/logs

echo "✨ E-Lib is ready to serve requests!"
echo "🌐 Application URL: https://${RENDER_EXTERNAL_HOSTNAME:-localhost}"

# Execute the main command
exec "$@"
