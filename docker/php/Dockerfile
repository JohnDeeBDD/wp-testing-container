FROM wordpress:cli-php8.2

# The CLI image runs as uid 33 (www-data) by default — switch to root
USER root

# Accept proxy env vars so apk/wget can reach the internet during build
ARG HTTP_PROXY
ARG HTTPS_PROXY
ARG http_proxy
ARG https_proxy
ARG NO_PROXY
ARG no_proxy

# Trust the host's CA certificates (needed when a TLS-intercepting proxy is present)
COPY ca-certificates.crt /etc/ssl/certs/ca-certificates.crt

# Increase PHP memory limit for WP-CLI operations (core download extraction, etc.)
RUN echo "memory_limit = 512M" > /usr/local/etc/php/conf.d/memory.ini

# Install Chromium + ChromeDriver for acceptance tests
RUN apk add --no-cache \
      chromium \
      chromium-chromedriver \
      nss \
      freetype \
      harfbuzz \
      ttf-freefont

# Install Composer globally
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory to the mounted project
WORKDIR /app

# Stay as root so WP-CLI --allow-root works and composer can write vendor/
CMD ["tail", "-f", "/dev/null"]
