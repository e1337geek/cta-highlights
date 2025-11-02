# CTA Highlights Development Container
FROM mcr.microsoft.com/devcontainers/php:1-8.2-bullseye

# Install Node.js 18 LTS
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs \
    && npm install -g npm@latest

# Install additional system dependencies
RUN apt-get update && export DEBIAN_FRONTEND=noninteractive \
    && apt-get -y install --no-install-recommends \
    git \
    zip \
    unzip \
    rsync \
    default-mysql-client \
    && apt-get clean -y \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions required for WordPress and development
RUN install-php-extensions \
    gd \
    mysqli \
    opcache \
    zip \
    exif \
    pcntl \
    bcmath \
    soap \
    intl \
    mbstring \
    xdebug

# Configure Xdebug for development
RUN echo "xdebug.mode=develop,debug" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.start_with_request=yes" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.client_host=host.docker.internal" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# Install Docker CLI (for running wp-env inside container)
RUN curl -fsSL https://download.docker.com/linux/debian/gpg | apt-key add - \
    && echo "deb [arch=amd64] https://download.docker.com/linux/debian bullseye stable" > /etc/apt/sources.list.d/docker.list \
    && apt-get update \
    && apt-get install -y docker-ce-cli \
    && apt-get clean -y \
    && rm -rf /var/lib/apt/lists/*

# Set up non-root user (vscode user is created by base image)
ARG USERNAME=vscode
ARG USER_UID=1000
ARG USER_GID=$USER_UID

# Add user to docker group for Docker socket access
RUN groupadd -f docker \
    && usermod -aG docker ${USERNAME}

# Create workspace directory
RUN mkdir -p /workspace \
    && chown -R ${USERNAME}:${USERNAME} /workspace

# Switch to non-root user
USER ${USERNAME}

# Set working directory
WORKDIR /workspace

# Install global npm packages for development
RUN npm install -g @wordpress/env

# Verify installations
RUN php -v \
    && composer --version \
    && node -v \
    && npm -v \
    && docker --version

# Welcome message
RUN echo 'echo "🎉 CTA Highlights development container ready!"' >> /home/${USERNAME}/.bashrc \
    && echo 'echo ""' >> /home/${USERNAME}/.bashrc \
    && echo 'echo "Quick start:"' >> /home/${USERNAME}/.bashrc \
    && echo 'echo "  npm install              - Install dependencies"' >> /home/${USERNAME}/.bashrc \
    && echo 'echo "  npm run env:start        - Start WordPress"' >> /home/${USERNAME}/.bashrc \
    && echo 'echo "  npm run test:quick       - Run quick tests"' >> /home/${USERNAME}/.bashrc \
    && echo 'echo "  npm run build:zip        - Build plugin ZIP"' >> /home/${USERNAME}/.bashrc \
    && echo 'echo ""' >> /home/${USERNAME}/.bashrc
