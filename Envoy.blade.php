@setup
    $migrate = isset($migrate) ? "-migrate" : "";
    $seed = isset($seed) ? "-s" : "";
    $target = isset($target) ? $target : "user@host";
@endsetup

@servers(['server' => [$target]])

@task('install', ['on' => 'server', 'confirm' => false])
    cd /path/to/app/root/folder/on/the/server
    sh install.sh {{$migrate}} {{$seed}}
@endtask



