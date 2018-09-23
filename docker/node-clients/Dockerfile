FROM node:6

RUN apt-get update && apt-get install apt-transport-https lsb-release ca-certificates wget -y
RUN wget -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg
RUN sh -c 'echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/php.list'
RUN apt-get update

# Install packages.
RUN apt-get update && apt-get install -y \
	nano \
	git \
	curl \
	wget \
	zip \
	unzip \
	supervisor \
	lsof \
  pv
RUN apt-get clean

# Install Node packages.
RUN npm install -g serve gulp gulp-cli gulp-babel babel-preset-es2015 yo https://gitlab.com/drypack/c2yogenerator \
eslint eslint-plugin-angular protractor protractor-console generator-angular @angular/platform-browser webdriver-manager

# Install openjdk, Chrome and update webdriver-manager (necessary for running protractor e2e test)
RUN apt-get update && \
  apt-get install openjdk-8-jdk-headless -y && \
  wget https://dl.google.com/linux/direct/google-chrome-stable_current_amd64.deb && \
  dpkg -i google-chrome-stable_current_amd64.deb; apt-get -fy install

RUN webdriver-manager update

EXPOSE 5000

