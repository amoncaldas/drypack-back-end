# Creating the development environment with Docker #

Quick and easy to use Docker container for the DryPack *local development*. It contains a set of servers/services and tools that allow us to make a good and professional development. It is based on [Debian Stretch](https://wiki.debian.org/DebianStretch).

## Summary ##

What is Docker? "Docker allows you to package your application with all of its dependencies into a standardized unit called container." When launching, the container will contain a pre-configured Laravel/php/node environment

To understand what is Docker, its concepts and how to use it you can read:

* This very good book for beginners: [Docker for developers book](https://leanpub.com/docker-for-developers).
* Of course you can go to the official docs: [Docker get started page](https://docs.docker.com/get-started/).

## Tutorial ##

This tutorial includes instructions to help you install Docker, build the project image and run the docker machine, as well as information about how to use the tools included in the solution.

## Installing Docker ##

The Docker solution is composed of three softwares: Docker Engine, Docker Compose and Docker Machine. We need to install the three. This tutorial covers the installation of docker in a Linux Debian based distribution. If you are using a non Debian based distribution you may have to make some adaptations. If yu are using windows, you should follow [this install Docker on Windows tutorial](https://docs.docker.com/docker-for-windows/).

To install the docker engine, in your terminal, run the following commands: 

```sh
sudo su
wget -qO- https://get.docker.com/ | sh
exit
sudo usermod -a -G docker $USER
su - $USER
```

To test if docker engine was installed run the command below. If it is, the hello-world docker container will say it explicitly.  Just read the screen!

```sh
docker run hello-world
```

To install the docker composer, in your terminal, run the following commands:  

```sh
sudo su
curl -L https://github.com/docker/compose/releases/download/1.6.2/docker-compose\
-`uname -s`-`uname -m` > /usr/local/bin/docker-compose
chmod +x /usr/local/bin/docker-compose
```

To test if docker compose was installed run the command below (you must see the version):   

```sh
docker-compose version
```

To install the docker machine, in your terminal, run the following commands:

```sh
curl -L https://github.com/docker/machine/releases/download/v0.6.0/docker-machine\
-`uname -s`-`uname -m` > /usr/local/bin/docker-machine && \
chmod +x /usr/local/bin/docker-machine
```

To test if docker compose was installed run the command below (you must see the version):

```sh
docker-machine version
```

Building the image

The docker image we are gonna create to setup the environment using docker wil contains:

* Postgres 9.5
* Postgresql Client
* Postgis 2.2
* Postgis Scripts
* PHP 7.0
* Composer  - [A tool for dependency management in PHP](https://getcomposer.org/doc/00-intro.md)
* Supervisor [A tool to monitor and control a number of processes](http://supervisord.org/)
* wget
* unzip
* cron
* gnupg - [To encrypt and sign your data](http://do.co/29H6Lof)
* Cur
* PHP CLI
* PHP mbstring
* php GD
* php-imagick
* PHP Curl
* PHP Xdebug (to debug PHP applications)
* PHP sqlite3
* PHP XML
* PHP Zip
* Nano
* Git
* Node
*	lsof

Go to the root folder of the project and then to the docker folder:

```sh
cd path/to/root/project/dir
cd docker
```

Build the docker image running (dnt forget the final dot "."):

```sh
docker build -t drypack-image-dev .
```

*If the docker daemon can not resolve some dsn, follow this guide*:
[Docker build could not resolve DNS](https://stackoverflow.com/questions/24991136/docker-build-could-not-resolve-archive-ubuntu-com-apt-get-fails-to-install-a)

## Running ##

To run the just built image as a container the first time, execute:

```sh
docker run --name drypack-container-dev \
-p 5435:5432 -p 5000:5000 \
-v $PWD:/var/www/ \
-d drypack-image-dev
```

### Important ###

The "docker run" command must be used only the first time you want to run a image. To stop the container, execute "docker stop drypack-container-dev". To run it again, type "docker start drypack-container-dev". Remember, only use "docker run ..." once per image. The commands in sequence are:

```sh
docker run --name drypack-container-dev #...and the rest of the command
docker stop drypack-container-dev
docker start drypack-container-dev
```

Then use docker stop and start every time you need.

To check if the container is running, just execute:

```sh
docker ps -a
```

### Passwords ### 

* Pgsql: `root:` (no password);
* DryPack: `drypackuser:drypack`

### Exposed ports ###

* 80 and 443 (Apache)
* 5432 (Postgres)

### Go inside ###

To go inside the virtual machine e execute commands there, just type:

```sh
docker exec -it --user root drypack-container-dev bash``


### PgSql ###

The PgSql port `5432` is exposed and Mapped to `5435`. The root account for PgSql is `root` (no password) and normal account is 'drypackuser' with password 'drypack'
