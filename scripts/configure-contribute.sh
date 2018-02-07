#!/bin/bash

# This script sets up the project environment and the project in the contribute mode.
# It means that the project will be ready for contribution, so you can make changes
# in the DryPack source code and then send a push request.
# In this mode, the client and admin front-ends are kept in a
# separated git repository (as it is in the original DryPack repository).

# So, if you make changes in any front-end code,
# you have to go to the front-end root folder and commit there.
# For example, "cd public/admin && git status". The same for the client front-end

cd public

# download and integrate the admin front-end
git clone git@gitlab.com:drypack/front-end-admin.git admin;
# download and integrate the client front-end
git clone git@gitlab.com:drypack/front-end-client.git client;

# go back to project root folder
cd ../

# Creating local env file
cp .env.example .env

# Check if docker engine and drypack image exist.
# If not, install docker and create the drypack mage, a container and run it.
# This script is called from project's root folder,
# so even if the install-docker.sh file resides in the same folder
# it is needed to give the full path to run it
sh scripts/setup-docker.sh

## INSIDE DOCKER FROM HERE ##

# Giving permissions to execute bash scripts
docker exec --user root drypack-container-dev /bin/sh -c "chmod +x scripts/e2e-test.sh"
docker exec --user root drypack-container-dev /bin/sh -c "chmod +x scripts/php-test.sh"

# Installing back-end dependencies
docker exec --user root drypack-container-dev /bin/sh -c "COMPOSER_PROCESS_TIMEOUT=2000 composer install"
docker exec --user root drypack-container-dev /bin/sh -c "composer install;composer update"

# Updating the webdriver to run the e2e tests
docker exec --user root drypack-container-dev /bin/sh -c "webdriver-manager update"

# Installing the front-end generator
docker exec --user root drypack-container-dev /bin/sh -c "npm install -g git+ssh://git@gitlab.com:drypack/c2yogenerator.git"

# Giving permissions in the laravel folders
chmod 777 -R storage;
chmod 777 -R bootstrap/cache;

# Runing the migrations and first database seed
docker exec --user root drypack-container-dev /bin/sh -c "php artisan migrate --seed"

# Installing front-end dependencies
docker exec --user root drypack-container-dev /bin/sh -c "cd public/admin;npm install;npm rebuild node-sass;cd ../.."
docker exec --user root drypack-container-dev /bin/sh -c "cd public/client;npm install;npm rebuild node-sass;cd ../.."

# Increasing the max watch files number
docker exec --user root drypack-container-dev /bin/sh -c "echo fs.inotify.max_user_watches=524288 | sudo tee -a /etc/sysctl.conf && sudo sysctl -p"

# build front-ends and run server
docker exec --user root drypack-container-dev /bin/sh -c "cd public/admin;gulp build;cd ../.."
docker exec --user root drypack-container-dev /bin/sh -c "cd public/client;gulp build;cd ../.."
docker exec --user root drypack-container-dev /bin/sh -c "npm run server"

