#!/bin/bash
# Initialize SSL certificates with Let's Encrypt
# Usage: ./scripts/init-ssl.sh your-domain.com your-email@example.com

set -e

DOMAIN=$1
EMAIL=$2

if [ -z "$DOMAIN" ] || [ -z "$EMAIL" ]; then
    echo "Usage: $0 <domain> <email>"
    echo "Example: $0 example.com admin@example.com"
    exit 1
fi

echo "Initializing SSL for $DOMAIN..."

# Create directories
mkdir -p nginx/ssl/live/$DOMAIN
mkdir -p nginx/certbot

# Generate temporary self-signed certificate
openssl req -x509 -nodes -newkey rsa:2048 \
    -days 1 \
    -keyout nginx/ssl/live/$DOMAIN/privkey.pem \
    -out nginx/ssl/live/$DOMAIN/fullchain.pem \
    -subj "/CN=$DOMAIN"

echo "Temporary certificate created"

# Start nginx
docker-compose -f docker-compose.yml -f docker-compose.nginx.yml up -d nginx

echo "Requesting Let's Encrypt certificate..."

# Request real certificate
docker-compose -f docker-compose.yml -f docker-compose.nginx.yml run --rm certbot \
    certonly --webroot \
    -w /var/www/certbot \
    -d $DOMAIN \
    --email $EMAIL \
    --agree-tos \
    --no-eff-email \
    --force-renewal

# Reload nginx
docker-compose -f docker-compose.yml -f docker-compose.nginx.yml exec nginx nginx -s reload

echo "SSL certificate installed successfully!"
