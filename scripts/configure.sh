#!/bin/bash

# This script sets up the project environment for a project based in DryPack

EXISTING_PROJECT=false
GIT_URL=false

while getopts g:e option
do
  case "${option}"
  in
      g) GIT_URL=${OPTARG};;
      e) EXISTING_PROJECT=true;;
  esac
done

if [ $EXISTING_PROJECT = false ]
then
  if [ $GIT_URL = true ]; then
    # Configuring the GIT
    rm -rf .git
    git init
    git remote add origin $GIT_URL
  fi

  # Creating local env file
  cp .env.example .env

  # download and integrate the admin front-end
  cd public
  git clone git@gitlab.com:drypack/front-end-admin.git admin;

  # remove git ignore for client front-end (in business project the whole solution are normally in the same repo)
  cd admin
  rm -rf .git # removing sub projects git repository
  cd ../..  # go back to the project root dir
  sed -i '/public\/admin/d' .gitignore # remove git ignore for admin

  # download and integrate the client front-end
  cd public
  git clone git@gitlab.com:drypack/front-end-client.git client;

  # remove git ignore for client front-end (in business project the whole solution are normally in the same repo)
  cd client
  rm -rf .git # removing sub projects git repository
  cd ../..  # go back to the project root dir
  sed -i '/public\/client/d' .gitignore

  echo "0.0.1" > VERSION;

  # Adding the reference to the DryPack original repository
  git remote add drypack-repo git@gitlab.com:drypack/back-end-php.git;

  # go back to project root folder
  cd ../
fi

# Check if docker engine and drypack image exist.
# If not, install docker and create the drypack mage, a container and run it.
# This script is called from project's root folder,
# so even if the install-docker.sh file resides in the same folder
# it is needed to give the full path to run it
sh setup-docker.sh

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

# Generating the keys
docker exec --user root drypack-container-dev /bin/sh -c "php artisan key:generate"
docker exec --user root drypack-container-dev /bin/sh -c "php artisan jwt:secret -f"

# Runing the migrations and first database seed
docker exec --user root drypack-container-dev /bin/sh -c "php artisan migrate --seed"

# Installing front-end dependencies
docker exec --user root drypack-container-dev /bin/sh -c "cd public/admin;npm install;npm rebuild node-sass;cd ../.."
docker exec --user root drypack-container-dev /bin/sh -c "cd public/client;npm install;npm rebuild node-sass;cd ../.."

# Increasing the max watch files number
docker exec --user root drypack-container-dev /bin/sh -c "echo fs.inotify.max_user_watches=524288 | sudo tee -a /etc/sysctl.conf && sudo sysctl -p"

if [ $EXISTING_PROJECT = false ]
then
  # Adding the changes to the stagin area. It is not committed automatically, bacause maybe we are inside docker
  git add .
fi

# build front-ends and run server
docker exec --user root drypack-container-dev /bin/sh -c "cd public/admin;gulp build;cd ../.."
docker exec --user root drypack-container-dev /bin/sh -c "cd public/client;gulp build;cd ../.."
docker exec --user root drypack-container-dev /bin/sh -c "npm run server"

