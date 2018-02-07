# We need to install dependencies only for Docker
[[ ! -e /.dockerenv ]] && exit 0

set -xe

# Installing general dependencies and php
apt-get update -yqq

apt-get install -yqq \
  libxml2-dev \
  libfreetype6-dev \
  libjpeg62-turbo-dev \
  libmcrypt-dev \
  libpng12-dev \
  libssl-dev \
  libpq-dev \
  git \
  wget \
  vim \
  unzip \
  postgresql-client \
  libnotify4 \
  libnss3 \
  && docker-php-ext-install zip pdo_pgsql mbstring mbstring mcrypt xml tokenizer \
  && docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ \
  && docker-php-ext-install gd \
  && apt-get clean

# To the coverage
pecl install xdebug && docker-php-ext-enable xdebug

# Downloading and installing composer
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install phpunit, the tool that we will use for testing
curl --location --output /usr/local/bin/phpunit https://phar.phpunit.de/phpunit.phar
chmod +x /usr/local/bin/phpunit
