@setup

@endsetup

@servers(['web' => ['user@host']])

@task('install', ['on' => 'web', 'confirm' => false])
    cd /path/to/app/root/dir
    sh install.sh
@endtask

@task('migrate', ['on' => 'web', 'confirm' => false])
    cd /path/to/app/root/dir
    docker exec -it --user root dry_app_server /bin/sh -c "php /var/www/artisan migrate"
@endtask

@task('seed', ['on' => 'web', 'confirm' => false])
    cd /path/to/app/root/dir
    docker exec -it --user root dry_app_server /bin/sh -c "php /var/www/artisan seed"
@endtask


