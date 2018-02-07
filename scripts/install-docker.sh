#!/bin/bash

DOCKER_COMPOSE_VERSION="1.18.0"
DOCKER_MACHINE_VERSION="v0.13.0"

# function to install docker
installDocker() {
  sudo wget -qO- https://get.docker.com/ | sh
  sudo usermod -a -G docker $USER

  docker run hello-world

  sudo curl -L https://github.com/docker/compose/releases/download/$DOCKER_COMPOSE_VERSION/docker-compose-`uname -s`-`uname -m` | \
  sudo tee /usr/local/bin/docker-compose > /dev/null
  sudo chmod +x /usr/local/bin/docker-compose

  docker-compose version

  sudo curl -L https://github.com/docker/machine/releases/download/$DOCKER_MACHINE_VERSION/docker-machine-`uname -s`-`uname -m` | \
  sudo tee /usr/local/bin/docker-machine > /dev/null
  sudo chmod +x /usr/local/bin/docker-machine

  docker-machine version
}

## Check if docker is installed. If not, install it
which docker

if [ $? -eq 0 ]
then
    docker --version | grep "Docker version"
    if [ $? -eq 0 ]
    then
        echo "Docker installed"
    else
        echo "Docker not installed, installing it..."
        installDocker
    fi
else
    echo "Docker not installed, installing..."
    installDocker
fi

