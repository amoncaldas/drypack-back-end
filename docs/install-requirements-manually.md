# Creating DryPack environment manually #

The following instructions intended to reproduce the environment defined in the Docker version (that is recommended to be used instead). Despite of the instructions, it has not been widely tested, so depending on the computer configuration and other features maybe it will be needed to make some adaptations.

## Requirements ##

- Non blocked/restricted access to Internet so that the dependencies can be download automatically.
- It is recommended o use Linux with APT.
- A decent editor. It is recommended [Visual Studio Code](https://code.visualstudio.com/).
- Most recent version of [GIT](https://git-scm.com/book/pt-br/v1/Primeiros-passos-Instalando-Git).
- NodeJS version 6.x.x ([tutorial to install](https://nodejs.org/en/download/package-manager/)).
- Configure the npm to run without sudo ([tutorial](https://docs.npmjs.com/getting-started/fixing-npm-permissions)).
- Check the npm version **npm --version** (must be >= a 3.10.10).
- PHP 7.0.x ([tutorial to install](http://tecadmin.net/install-php5-on-ubuntu/)).
- PHP extensions: fileinfo, mbstring, php7.0-pgsql, php7.0-zip, php7.0-cli, php7.0-common, php7.0-gd, php7.0-mbstring, php7.0-mcrypt, php7.0-readline, php7.0-json, php-imagick, php7.0-sqlite3, php7.0-xml, php7.0-curl, php7.0-fpm and php-xdebug
- Composer ([tutorial to install](https://getcomposer.org/doc/00-intro.md#globally)).
- PostgreSQL ([tutorial to install](https://www.vivaolinux.com.br/dica/Instalando-o-PostgreSQL-e-pgAdmin3-no-Ubuntu)).

## Installing the requirements ##

*Important: if the requirements are already installed, go to the configuration step.*

- In a clean Debian like Linux installation, the follow commands install all the requirements:

```sh
curl -sL https://deb.nodesource.com/setup_6.x | sudo bash -
```

```sh
sudo apt-get update && sudo apt-get install -y build-essential libxml2-dev libfreetype6-dev libjpeg-turbo8-dev libmcrypt-dev libpng12-dev libssl-dev libpq-dev vim unzip postgresql-9.5 nodejs php7.0 php7.0-pgsql php7.0-zip php7.0-cli php7.0-common php7.0-gd php7.0-mbstring php7.0-mcrypt php7.0-readline php7.0-json php-imagick php7.0-sqlite3 php7.0-xml php7.0-curl php7.0-fpm php-xdebug pgadmin3 nano git  curl openssh-server phppgadmin wget zip cron supervisor software-properties-common postgresql-client-9.5 postgresql-contrib-9.5
```

```sh
sudo curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer
```

```sh
mkdir ~/.npm-global
npm config set prefix '~/.npm-global'
```

*If you need support for spatial data in the database, you also have to run:*

```sh
sudo apt-get update && sudo apt-get install -y postgresql-9.5-postgis-2.2 postgresql-9.5-postgis-scripts
```

- Add the line below in the end of the file **~/.bashrc**.

```sh
export PATH=~/.npm-global/bin:$PATH
```

- Run the following commands to complete the database configuration:

```sh
sudo -u postgres psql
alter user postgres password 'root';
CREATE USER drypackuser WITH SUPERUSER PASSWORD 'drypack';
CREATE DATABASE drypack_db OWNER drypackuser;
CREATE DATABASE drypack_teste_db OWNER drypackuser;
\q
```

- If you added the Postgis spatial extension, you must also run the following command:

```sh
sudo -u postgres psql
CREATE EXTENSION adminpack;
CREATE DATABASE gisdb;
\connect gisdb;
CREATE EXTENSION postgis
\q
```

- Run the following commands to complete the configuration:

```sh
source ~/.bashrc
echo fs.inotify.max_user_watches=524288 | sudo tee -a /etc/sysctl.conf && sudo sysctl -p
sudo chown $(whoami):$(whoami) -R ~/.composer
```

- Install NODEJS/NPM

```sh
curl -sL https://deb.nodesource.com/setup_6.x | bash - && \
apt-get update && \
apt-get install -y nodejs
``` 
 
- Install node and needed packages:

```sh
npm install -g serve gulp gulp-cli bower yo generator-karma karma \
angular-material angular @uirouter/angular @angular/core @angular/common angular-route @angular/router gulp-babel babel-preset-es2015 \
eslint eslint-plugin-angular protractor protractor-console generator-angular @angular/platform-browser webdriver-manager
```
