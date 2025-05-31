# Portfolio API - Optimized for Oracle Container Engine for Kubernetes (OKE)
# Multi-stage build for minimal production image

# Build stage
FROM php:8.4-fpm-alpine AS builder

# Install build dependencies
RUN apk add --no-cache \
    curl \
    libzip-dev \
    oniguruma-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    mysql-client \
    git \
    unzip

# Install PHP extensions optimized for OCI
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mysqli \
        zip \
        gd \
        mbstring \
        opcache

# Install Redis extension for OCI Redis cache
RUN pecl install redis && docker-php-ext-enable redis

# Production stage
FROM php:8.4-fpm-alpine AS production

# Install runtime dependencies only
RUN apk add --no-cache \
    mysql-client \
    nginx \
    supervisor \
    curl

# Copy PHP extensions from builder
COPY --from=builder /usr/local/lib/php/extensions/ /usr/local/lib/php/extensions/
COPY --from=builder /usr/local/etc/php/conf.d/ /usr/local/etc/php/conf.d/

# Create application user
RUN addgroup -g 1000 -S app && \
    adduser -u 1000 -S app -G app

# Set working directory
WORKDIR /var/www/html

# Copy application code
COPY --chown=app:app . .

# Create necessary directories
RUN mkdir -p storage/cache storage/logs storage/uploads && \
    chown -R app:app storage && \
    chmod -R 755 storage

# PHP configuration for OKE
COPY docker/php.ini /usr/local/etc/php/
COPY docker/php-fpm.conf /usr/local/etc/php-fpm.d/
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Health check for OKE readiness/liveness probes
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/api/health || exit 1

# Expose port
EXPOSE 80

# Switch to app user
USER app

# Start supervisor (manages nginx + php-fpm)
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
