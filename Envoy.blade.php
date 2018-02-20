@servers(['staging' => ['user@host'], 'development' => ['user@host'], 'production' => ['user@host']])

@task('install', ['on' => $env, 'confirm' => false])
    cd /path/to/project/root/dir
    sh install.sh
@endtask

@task('migrate', ['on' => $env, 'confirm' => false])
    docker exec -it --user root dry_app_server bash
    cd /var/www
    php artisan migrate
    exit
@endtask

@task('seed', ['on' => $env, 'confirm' => false])
    docker exec -it --user root dry_app_server bash
    cd /var/www
    php artisan seed
    exit
@endtask


