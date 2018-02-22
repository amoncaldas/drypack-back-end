@setup
    $migrate = isset($migrate) ? "-migrate" : "";
    $seed = isset($seed) ? "-s" : "";
@endsetup

@servers(['staging' => ['user@host'], 'development' => ['user@host'], 'production' => ['user@host']])

@task('install', ['on' => $env, 'confirm' => false])
    cd /path/to/project/root/dir
    sh install.sh {{$migrate}} {{$seed}}
@endtask



