FROM wordpress:cli-php8.2

# The CLI image runs as uid 33 (www-data) by default — switch to root
USER root

# Increase PHP memory limit for WP-CLI operations (core download extraction, etc.)
RUN echo "memory_limit = 512M" > /usr/local/etc/php/conf.d/memory.ini

# Install Composer globally
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory to the mounted project
WORKDIR /app

# Stay as root so WP-CLI --allow-root works and composer can write vendor/
CMD ["tail", "-f", "/dev/null"]
