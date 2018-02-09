# Common errors and solutions #

This docs list some common errors and the solutions for the the DryPack solution

## Missing binding ##

*Missing binding /var/www/public/admin/node_modules/node-sass/vendor/linux-x64-48/binding.node*

```sh
npm rebuild node-sass
```

## Class not found ##

```sh
php artisan vendor:publish
composer dump-autoload
```

## Missing key ##

```sh
php artisan key:generate
```

## Cache error ##

```sh
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

## Broken packages while running docker-install script ##

Error: "E: Unable to correct problems, you have held broken packages."

```sh
# on Debian like distributions
sudo apt-get install aptitude
sudo aptitude docker-ce
```

## Docker installation error when running configure ##

Purge any other previous/old docker installation:

```sh
sudo apt-get purge -y docker-engine
sudo rm -rf /usr/local/bin/docker-compose
sudo rm -rf /usr/local/bin/docker-machine
sudo apt-get autoremove -y --purge docker-engine
sudo apt-get autoremove -y --purge docker-ce
sudo apt-get autoclean
```