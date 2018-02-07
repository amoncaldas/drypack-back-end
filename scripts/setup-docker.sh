#!/bin/bash

# Run docker installer srcipt
# This script is called from project's root folder,
# so even if the install-docker.sh file resides in the same folder
# it is needed to give the full path to run it
sh scripts/install-docker.sh

## Check if drypack docker image available. If not, create it

if [ docker images | grep -q drypack-image-dev 2> /dev/null ];
then
  echo "Drypack docker image already exist. Starting it ..."
else
  echo "Drypack docker not found. It is gonna be created"
  cd docker
  docker build -t drypack-image-dev .
  cd ..
  docker run --name drypack-container-dev \
  -p 5435:5432 -p 5000:5000 \
  -v $PWD:/var/www/ \
  -d drypack-image-dev
fi

docker start drypack-container-dev

