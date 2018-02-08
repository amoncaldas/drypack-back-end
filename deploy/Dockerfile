FROM debian:stretch
ENV DEBIAN_FRONTEND noninteractive

# Install packages.
RUN apt-get update
RUN apt-get install -y \
	nano \
	git \
  apache2 \
  libapache2-mod-php7.0 \
	php7.0 \
	php7.0-cli \
  php7.0-mbstring \
	php7.0-sqlite3 \
  php7.0-xml \
	php7.0-gd \
	php7.0-curl \
	php7.0-fpm \
	php-xdebug \
	php7.0-pgsql \
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

# setup POSTGRES SECTION
# Add the PostgreSQL PGP key to verify their Debian packages.
# It should be the same key as https://www.postgresql.org/media/keys/ACCC4CF8.asc
#RUN apt-key adv --keyserver hkp://p80.pool.sks-keyservers.net:80 --recv-keys B97B0AFCAA1A47F044F244A07FCC7D46ACCC4CF8

# Add PostgreSQL's repository. It contains the most recent stable release
#     of PostgreSQL, ``9.5``.
RUN echo "deb http://apt.postgresql.org/pub/repos/apt/ stretch-pgdg main" > /etc/apt/sources.list.d/pgdg.list

RUN wget --quiet -O - https://www.postgresql.org/media/keys/ACCC4CF8.asc | apt-key add -

#  There are some warnings (in red) that show up during the build. You can hide
#  them by prefixing each apt-get statement with DEBIAN_FRONTEND=noninteractive
RUN apt-get update && apt-get install -y software-properties-common postgresql-9.5 postgresql-client-9.5 postgresql-contrib-9.5 postgresql-9.5-postgis-2.2 postgresql-9.5-postgis-scripts

# Note: The official Debian and Ubuntu images automatically ``apt-get clean``
# after each ``apt-get``

# Run the rest of the commands as the ``postgres`` user created by the ``postgres-9.5`` package when it was ``apt-get installed``
USER postgres

# Create a PostgreSQL role named ``drypackuser`` with ``drypack`` as the password and
# then create a database `drypack_db` owned by the ``drypackuser`` role.
# then create a database `drypack_teste_db` owned by the ``drypackuser`` role.
# Setup PostGIS
# Note: here we use ``&&\`` to run commands one after the other - the ``\``
# allows the RUN command to span multiple lines.
RUN  /etc/init.d/postgresql start &&\
  psql --command "CREATE USER drypackuser WITH SUPERUSER PASSWORD 'drypack';" &&\
	psql --command "CREATE DATABASE drypack_db OWNER drypackuser;" &&\
	psql --command "CREATE DATABASE drypack_teste_db OWNER drypackuser;" &&\
	psql --command "CREATE EXTENSION adminpack;" && \
	psql --command "CREATE DATABASE gisdb;" && \
	psql --command "\connect gisdb;" && \
	psql --command "CREATE EXTENSION postgis;" && \
  createdb -O drypackuser drypack

# Adjust PostgreSQL configuration so that remote connections to the
# database are possible
## DISABLED in prodction environment!
# RUN echo "host all  all    0.0.0.0/0  md5" >> /etc/postgresql/9.5/main/pg_hba.conf
# RUN echo "listen_addresses='*'" >> /etc/postgresql/9.5/main/postgresql.conf

# Add VOLUMEs to allow backup of config, logs and databases
VOLUME  ["/etc/postgresql", "/var/log/postgresql", "/var/lib/postgresql"]

# Set the default command to run when starting the container
CMD ["/usr/lib/postgresql/9.5/bin/postgres", "-D", "/var/lib/postgresql/9.5/main", "-c", "config_file=/etc/postgresql/9.5/main/postgresql.conf"]

USER root
# END setup POSTGRES SECTION

# Setup locale.
RUN sed -i -e 's/# en_US.UTF-8 UTF-8/en_US.UTF-8 UTF-8/' /etc/locale.gen && locale-gen
ENV LANG en_US.UTF-8
ENV LANGUAGE en_US:en
ENV LC_ALL en_US.UTF-8

# Setup Apache.
RUN sed -i 's/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf
RUN sed -i 's/DocumentRoot \/var\/www\/html/DocumentRoot \/var\/www/' /etc/apache2/sites-available/000-default.conf
RUN sed -i 's/DocumentRoot \/var\/www\/html/DocumentRoot \/var\/www/' /etc/apache2/sites-available/default-ssl.conf
RUN echo "Listen 8080" >> /etc/apache2/ports.conf
RUN echo "Listen 8081" >> /etc/apache2/ports.conf
RUN echo "Listen 8443" >> /etc/apache2/ports.conf
RUN sed -i 's/VirtualHost \*:80/VirtualHost \*:\*/' /etc/apache2/sites-available/000-default.conf
RUN sed -i 's/VirtualHost __default__:443/VirtualHost _default_:443 _default_:8443/' /etc/apache2/sites-available/default-ssl.conf
RUN a2enmod rewrite
RUN a2enmod ssl
RUN a2ensite default-ssl.conf


# Setup Supervisor.
RUN echo '[program:postgresql]\ncommand=/usr/lib/postgresql/9.5/bin/postgres -D /var/lib/postgresql/9.5/main -c config_file=/etc/postgresql/9.5/main/postgresql.conf\nuser=postgres\nautorestart=true\n\n' >> /etc/supervisor/supervisord.conf
RUN echo '[program:cron]\ncommand=cron -f\nautorestart=false \n\n' >> /etc/supervisor/supervisord.conf

# XDebug is DISABLED by default in production invironment
# RUN echo "xdebug.max_nesting_level = 300" >> /etc/php/7.0/cli/conf.d/20-xdebug.ini
# RUN echo "xdebug.remote_enable = 1" >> /etc/php/7.0/cli/conf.d/20-xdebug.ini
# RUN echo "xdebug.remote_autostart = 1" >> /etc/php/7.0/cli/conf.d/20-xdebug.ini
# RUN echo "xdebug.remote_host=172.17.0.1" >> /etc/php/7.0/cli/conf.d/20-xdebug.ini

# Install Composer.
RUN curl -sS https://getcomposer.org/installer | php
RUN mv composer.phar /usr/local/bin/composer

# Update composer
#RUN composer global update

# Install NODEJS/NPM
RUN curl -sL https://deb.nodesource.com/setup_6.x | bash - && \
	apt-get update && \
	apt-get install -y nodejs

# Install Node packages.
RUN npm install -g serve gulp gulp-cli yo generator-karma karma \
angular-animate angular-aria angular-material angular angular-messages @uirouter/angular @angular/core @angular/common angular-route @angular/router gulp-babel babel-preset-es2015 \
eslint eslint-plugin-angular protractor protractor-console generator-angular @angular/platform-browser webdriver-manager

EXPOSE 80 5432 443
CMD exec supervisord -n
WORKDIR /var/www/
