FROM debian:stretch
ENV DEBIAN_FRONTEND noninteractive

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
# database are possible.
RUN echo "host all  all    0.0.0.0/0  md5" >> /etc/postgresql/9.5/main/pg_hba.conf

# And add ``listen_addresses`` to ``/etc/postgresql/9.5/main/postgresql.conf``
RUN echo "listen_addresses='*'" >> /etc/postgresql/9.5/main/postgresql.conf

# Add VOLUMEs to allow backup of config, logs and databases
VOLUME  ["/etc/postgresql", "/var/log/postgresql", "/var/lib/postgresql"]

USER root
# END setup POSTGRES SECTION

# Setup locale.
RUN sed -i -e 's/# en_US.UTF-8 UTF-8/en_US.UTF-8 UTF-8/' /etc/locale.gen && locale-gen
ENV LANG en_US.UTF-8
ENV LANGUAGE en_US:en
ENV LC_ALL en_US.UTF-8

# Setup PHP.
RUN sed -i 's/display_errors = Off/display_errors = On/' /etc/php/7.2/cli/php.ini
RUN sed -i 's/short_open_tag = Off/short_open_tag = On/' /etc/php/7.2/cli/php.ini

# Setup Supervisor.
RUN echo '[program:postgresql]\ncommand=/usr/lib/postgresql/9.5/bin/postgres -D /var/lib/postgresql/9.5/main -c config_file=/etc/postgresql/9.5/main/postgresql.conf\nuser=postgres\nautorestart=true\n\n' >> /etc/supervisor/supervisord.conf
RUN echo '[program:sshd]\ncommand=/usr/sbin/sshd -D\n\n' >> /etc/supervisor/supervisord.conf
RUN echo '[program:cron]\ncommand=cron -f\nautorestart=false \n\n' >> /etc/supervisor/supervisord.conf

# Setup XDebug.
RUN echo "xdebug.max_nesting_level = 300" >> /etc/php/7.2/cli/conf.d/20-xdebug.ini
RUN echo "xdebug.remote_enable = 1" >> /etc/php/7.2/cli/conf.d/20-xdebug.ini
RUN echo "xdebug.remote_autostart = 1" >> /etc/php/7.2/cli/conf.d/20-xdebug.ini
RUN echo "xdebug.remote_host=172.17.0.1" >> /etc/php/7.2/cli/conf.d/20-xdebug.ini

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
RUN npm install -g serve gulp gulp-cli gulp-babel babel-preset-es2015 \
eslint eslint-plugin-angular protractor protractor-console generator-angular @angular/platform-browser webdriver-manager

# Install openjdk, Chrome and update webdriver-manager (necessary for running protractor e2e test)
RUN apt-get update && \
  apt-get install openjdk-8-jdk-headless -y && \
  wget https://dl.google.com/linux/direct/google-chrome-stable_current_amd64.deb && \
  dpkg -i google-chrome-stable_current_amd64.deb; apt-get -fy install

RUN webdriver-manager update

EXPOSE 5432 443
CMD exec supervisord -n
WORKDIR /var/www/
