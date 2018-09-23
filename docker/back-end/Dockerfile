FROM jguyomard/laravel-php:7.2

RUN apt-get update && apt-get install apt-transport-https lsb-release ca-certificates wget -y
RUN wget -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg
RUN sh -c 'echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/php.list'
RUN apt-get update

# Install packages.
RUN apt-get update && apt-get install -y \
	nano \
	git \
	php7.2 \
	php-cli \
  php-mbstring \
	php-sqlite3 \
  php-xml \
	php-gd \
	php-curl \
	php-fpm \
	php-xdebug \
	php-pgsql \
	curl \
	wget \
	zip \
  php-zip \
	unzip \
	cron \
  gnupg \
	supervisor \
  gdal-bin \
	lsof \
  php-imagick \
  pv
RUN apt-get clean


# Setup PHP.
RUN sed -i 's/display_errors = Off/display_errors = On/' /etc/php/7.2/cli/php.ini
RUN sed -i 's/short_open_tag = Off/short_open_tag = On/' /etc/php/7.2/cli/php.ini

# Setup XDebug.
RUN echo "xdebug.max_nesting_level = 300" >> /etc/php/7.2/cli/conf.d/20-xdebug.ini
RUN echo "xdebug.remote_enable = 1" >> /etc/php/7.2/cli/conf.d/20-xdebug.ini
RUN echo "xdebug.remote_autostart = 1" >> /etc/php/7.2/cli/conf.d/20-xdebug.ini
RUN echo "xdebug.remote_host=172.17.0.1" >> /etc/php/7.2/cli/conf.d/20-xdebug.ini

# Install Composer.
RUN curl -sS https://getcomposer.org/installer | php
RUN mv composer.phar /usr/local/bin/composer

WORKDIR /var/www/
