# Multi-stage build for Course Enrollment System - Docker Hardened Images (DHI)
FROM dhi.io/php:8.2-debian13-dev AS builder

WORKDIR /app

# Copy application code
COPY . .

# Create uploads directory in builder (will be copied to runtime)
RUN mkdir -p uploads/{documents,payments}

# Production stage with FPM (includes PDO extensions)
FROM dhi.io/php:8.2-fpm

WORKDIR /app

# Copy PHP configuration
COPY php.ini /usr/local/etc/php/conf.d/99-custom.ini

# Copy application code and directories from builder
COPY --from=builder --chown=www-data:www-data /app .

EXPOSE 9000

USER www-data

CMD ["php-fpm"]
