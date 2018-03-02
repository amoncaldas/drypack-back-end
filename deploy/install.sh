MIGRATE=false
SEED=false

while getopts m:s option
do
  case "${option}"
  in
      m) MIGRATE=true;;
      s) SEED=true;;
  esac
done


# remove all dirs, except storage, that may contain application files
find . -maxdepth 1 -type d -not -name 'storage' -print0 | xargs -0 -I {} rm -rf {} >> /dev/null 2>&1

#remove all files starting with dot
find . -maxdepth 1 -type f -name '.*' -delete

# install docker, if not installed
sh install-docker.sh

# install unzip, if not installed
apt-get install unzip -y

# unzip package
unzip -o -q appPack.zip

# remove cache/session files
rm -rf storage/framework/sessions/*
rm -rf storage/framework/cache/*
rm -rf storage/framework/views/*

# remove setup files
rm install-docker.sh
rm .gitkeep
rm appPack.zip
rm install.sh

# start/restart docker-compose
docker-compose up -d

# the migration is not ran automatically

docker exec --user root dry_php_server /bin/sh -c 'echo "* * * * * php /var/www schedule:run >> /dev/null 2>&1" | crontab -'

printf 'Waiting the server to be alive...\n'
until $(curl --output /dev/null --silent --head --fail http://localhost:8080); do
    printf '.'
    sleep 1
done
printf 'Server is now alive!\n'


if [ $MIGRATE = true ]
then
  docker exec --user root dry_php_server /bin/sh -c "cd /var/www && php artisan migrate"
fi

if [ $SEED = true ]
then
  docker exec --user root dry_php_server /bin/sh -c "cd /var/www && php artisan db:seed"
fi

# Be aware that the deploy task when ran using artisan deploy checks this printed string below
# to determine if the installation succeeded. If you change this message, make sure to update the
# app/Console/Commands/Deploy.php on the deploy method.
printf 'Installation succeeded!\n'






