# remove all dirs
find . -maxdepth 1 -type d -not -name 'storage' -print0 | xargs -0 -I {} rm -rf {}

#remove all files starting with dot
find . -maxdepth 1 -type f -name '.*' -delete

# install docker, if not installed
sh install-docker.sh

# install unzip, if not installed
apt-get install unzip -y

# unzip package
unzip -o -q appPack.zip

# remove setupfiles
rm appPack.zip
rm install-docker.sh
rm install.sh
rm .gitkeep

# start/restart docker-compose
docker-compose up -d

# the migration is not ran automatically
# docker exec -it --user root dry_app_server /bin/sh -c "cd /var/www && php artisan migrate --seed"



