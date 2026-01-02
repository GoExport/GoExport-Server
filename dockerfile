# =============================================================================
# GoExport Server - Production Dockerfile
# =============================================================================
# Features:
#   - Headless virtual display (Xorg dummy driver) for GoExport screen capture
#   - PHP 8.2 with Laravel extensions
#   - Node.js for frontend assets
#   - Supervisor for process management
#   - Queue workers for 'default' and 'exports' queues
# ============================================================================

FROM ubuntu:24.04

LABEL maintainer="Your Name <your@email.com>"
LABEL description="GoExport Server - headless video export processing"

# =============================================================================
# Environment Configuration
# =============================================================================
ENV DEBIAN_FRONTEND=noninteractive
ENV TZ=UTC

# Display settings for headless Xvfb
ENV DISPLAY=:99
ENV DISPLAY_WIDTH=1280
ENV DISPLAY_HEIGHT=720
ENV DISPLAY_DEPTH=24

# PHP settings
ENV PHP_VERSION=8.2
ENV PHP_MEMORY_LIMIT=512M
ENV PHP_MAX_EXECUTION_TIME=300
ENV PHP_UPLOAD_MAX_FILESIZE=100M
ENV PHP_POST_MAX_SIZE=100M

# Laravel settings
ENV APP_ENV=production
ENV APP_DEBUG=false
ENV LOG_CHANNEL=stderr

# Node.js version
ENV NODE_VERSION=20

# =============================================================================
# System Dependencies
# =============================================================================
RUN apt-get update && apt-get install -y --no-install-recommends \
    # Basic utilities
    ca-certificates \
    curl \
    wget \
    gnupg \
    unzip \
    git \
    supervisor \
    cron \
    # Headless display (Xorg dummy driver)
    xserver-xorg-core \
    xserver-xorg-video-dummy \
    xserver-xorg-input-libinput \
    xserver-xorg-input-evdev \
    x11-xserver-utils \
    # Desktop environment and VNC
    xfce4 \
    xfce4-goodies \
    tigervnc-standalone-server \
    tigervnc-common \
    x11vnc \
    novnc \
    websockify \
    openbox \
    xterm \
    firefox \
    dbus-x11 \
    # X11 / input / rendering (you already had most of these)
    libx11-6 \
    libxext6 \
    libxrender1 \
    libxtst6 \
    libxi6 \
    libxrandr2 \
    libxcb1 \
    libxdamage1 \
    libxfixes3 \
    libxcomposite1 \
    libxkbcommon0 \
    libnspr4 \
    # GTK + rendering stack (REQUIRED for Chromium)
    libgtk-3-0 \
    libgdk-pixbuf-2.0-0 \
    libpango-1.0-0 \
    libpangocairo-1.0-0 \
    libcairo2 \
    libcairo-gobject2 \
    # Accessibility / ATK (Chromium requires these even headless)
    libatk1.0-0 \
    libatk-bridge2.0-0 \
    libatspi2.0-0 \
    # Printing, PCI, atomic ops
    libcups2 \
    libpci3 \
    libatomic1 \
    # Audio (Chromium still links this even if unused)
    libasound2t64 \
    libpulse0 \
    # PulseAudio for audio capture (required by FFmpeg)
    pulseaudio \
    pulseaudio-utils \
    libasound2-plugins \
    # Video / EGL
    libegl1 \
    libaom3 \
    # other chrome dependencies
    libnss3 \
    && rm -rf /var/lib/apt/lists/*

# =============================================================================
# PulseAudio Access Configuration
# =============================================================================
RUN usermod -aG pulse-access root \
    && usermod -aG pulse-access www-data

# =============================================================================
# PHP Installation
# =============================================================================
RUN apt-get update && apt-get install -y --no-install-recommends \
    software-properties-common \
    && add-apt-repository -y ppa:ondrej/php \
    && apt-get update && apt-get install -y --no-install-recommends \
    php${PHP_VERSION}-fpm \
    php${PHP_VERSION}-cli \
    php${PHP_VERSION}-common \
    php${PHP_VERSION}-mysql \
    php${PHP_VERSION}-pgsql \
    php${PHP_VERSION}-sqlite3 \
    php${PHP_VERSION}-redis \
    php${PHP_VERSION}-memcached \
    php${PHP_VERSION}-mbstring \
    php${PHP_VERSION}-xml \
    php${PHP_VERSION}-curl \
    php${PHP_VERSION}-zip \
    php${PHP_VERSION}-bcmath \
    php${PHP_VERSION}-intl \
    php${PHP_VERSION}-gd \
    php${PHP_VERSION}-imagick \
    php${PHP_VERSION}-opcache \
    php${PHP_VERSION}-readline \
    php${PHP_VERSION}-tokenizer \
    && rm -rf /var/lib/apt/lists/*

# Configure PHP-FPM
RUN sed -i "s/memory_limit = .*/memory_limit = ${PHP_MEMORY_LIMIT}/" /etc/php/${PHP_VERSION}/fpm/php.ini \
    && sed -i "s/max_execution_time = .*/max_execution_time = ${PHP_MAX_EXECUTION_TIME}/" /etc/php/${PHP_VERSION}/fpm/php.ini \
    && sed -i "s/upload_max_filesize = .*/upload_max_filesize = ${PHP_UPLOAD_MAX_FILESIZE}/" /etc/php/${PHP_VERSION}/fpm/php.ini \
    && sed -i "s/post_max_size = .*/post_max_size = ${PHP_POST_MAX_SIZE}/" /etc/php/${PHP_VERSION}/fpm/php.ini \
    && sed -i "s/;cgi.fix_pathinfo=1/cgi.fix_pathinfo=0/" /etc/php/${PHP_VERSION}/fpm/php.ini

# Configure PHP-FPM pool
RUN sed -i "s/listen = .*/listen = 9000/" /etc/php/${PHP_VERSION}/fpm/pool.d/www.conf \
    && sed -i "s/;clear_env = no/clear_env = no/" /etc/php/${PHP_VERSION}/fpm/pool.d/www.conf

# =============================================================================
# Nginx Installation
# =============================================================================
RUN apt-get update && apt-get install -y --no-install-recommends nginx \
    && rm -rf /var/lib/apt/lists/*

# =============================================================================
# Composer Installation
# =============================================================================
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# =============================================================================
# Node.js Installation
# =============================================================================
RUN curl -fsSL https://deb.nodesource.com/setup_${NODE_VERSION}.x | bash - \
    && apt-get install -y nodejs \
    && npm install -g npm@latest \
    && rm -rf /var/lib/apt/lists/*

# =============================================================================
# VNC Configuration
# =============================================================================
RUN mkdir -p /root/.vnc \
    && echo '#!/bin/sh\n\
unset SESSION_MANAGER\n\
unset DBUS_SESSION_BUS_ADDRESS\n\
test -x /usr/bin/dbus-launch && export $(dbus-launch)\n\
exec startxfce4' > /root/.vnc/xstartup \
    && chmod +x /root/.vnc/xstartup \
    && mkdir -p /var/log/supervisor

# =============================================================================
# Application Setup
# =============================================================================
WORKDIR /var/www/html

# Copy composer files first for better caching
COPY composer.json composer.lock* ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Copy package files for npm
COPY package.json package-lock.json* ./
RUN npm ci || npm install

# Copy application source
COPY . .

# Ensure .env.example exists for runtime setup
RUN test -f .env.example || echo "APP_NAME=GoExport" > .env.example

# Complete composer setup
RUN composer dump-autoload --optimize \
    && composer run-script post-autoload-dump || true

# Build frontend assets with all dev dependencies
RUN npm run build

# =============================================================================
# GoExport Binary Installation
# =============================================================================
# GoExport is downloaded at runtime in entrypoint.sh to reduce image size
RUN mkdir -p /var/www/html/bin/goexport

# =============================================================================
# Permissions & Storage
# =============================================================================
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Create required directories
RUN mkdir -p /var/www/html/storage/logs \
    && mkdir -p /var/www/html/storage/framework/cache \
    && mkdir -p /var/www/html/storage/framework/sessions \
    && mkdir -p /var/www/html/storage/framework/views \
    && mkdir -p /var/www/html/storage/app/public/exports

# =============================================================================
# Configuration Files
# =============================================================================

# Nginx configuration
COPY docker/nginx.conf /etc/nginx/sites-available/default

# Xorg configuration (for dummy driver)
COPY docker/xorg.conf /etc/xorg.conf

# Supervisor configuration
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# =============================================================================
# Healthcheck
# =============================================================================
HEALTHCHECK --interval=30s --timeout=10s --start-period=60s --retries=3 \
    CMD curl -f http://localhost/health || exit 1

# =============================================================================
# Ports
# =============================================================================
# 80    - HTTP (Nginx)
# 9000  - PHP-FPM
# 5901  - VNC port (Display :1 - Full Desktop)
# 5999  - VNC port (Display :99 - GoExport headless)
# 6080  - noVNC web interface (Display :1)
# 6099  - noVNC web interface (Display :99)
EXPOSE 80 5901 5999 6080 6099

# =============================================================================
# Startup
# =============================================================================
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

ENTRYPOINT ["/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
