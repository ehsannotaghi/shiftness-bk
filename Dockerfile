FROM php:8.1-cli-alpine

# Install system dependencies
RUN apk add --no-cache \
    postgresql-dev \
    git \
    curl

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_pgsql

# Set working directory
WORKDIR /app

# Copy application files
COPY . .

# Create a non-root user for security
RUN addgroup -g 1000 phpuser && adduser -D -u 1000 -G phpuser phpuser
USER phpuser

EXPOSE 8000

CMD ["php", "-S", "0.0.0.0:8000"]
