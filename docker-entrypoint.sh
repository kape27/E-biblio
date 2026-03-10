#!/bin/bash
set -e

echo "Starting E-Lib application..."

# Wait for database to be ready
echo "Waiting for database connection..."
until php -r "
\$maxRetries = 30;
\$retryDelay = 2;
for (\$i = 0; \$i < \$maxRetries; \$i++) {
    try {
        \$pdo = new PDO(
            'mysql:host=' . getenv('DB_HOST') . ';dbname=' . getenv('DB_NAME'),
            getenv('DB_USER'),
            getenv('DB_PASS')
        );
        echo 'Database connection successful' . PHP_EOL;
        exit(0);
    } catch (PDOException \$e) {
        if (\$i === \$maxRetries - 1) {
            echo 'Database connection failed: ' . \$e->getMessage() . PHP_EOL;
            exit(1);
        }
        sleep(\$retryDelay);
    }
}
"; do
  echo "Database is unavailable - retrying..."
  sleep 2
done

echo "Database is ready!"

# Set proper permissions
chown -R www-data:www-data /var/www/html/uploads
chown -R www-data:www-data /var/www/html/logs
chown -R www-data:www-data /var/www/html/backups

echo "E-Lib is ready to serve requests!"

# Execute the main command
exec "$@"
