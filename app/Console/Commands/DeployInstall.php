<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DeployInstall extends Command
{
  /**
  * The name and signature of the console command.
  *
  * @var string
  */
  protected $signature = 'deploy:install {--m|migrate=false}';

  /**
  * The console command description.
  *
  * @var string
  */
  protected $description = 'Run a previous sent remote installation on a remote ftp/sftp server';

  /**
  * Create a new command instance.
  *
  * @return void
  */
  public function __construct()
  {
    parent::__construct();
  }

  /**
  * Execute the console command.
  *
  * @return mixed
  */
  public function handle()
  {
    // can be: development / staging / production
    $this->env = $this->option('env');

    if (!isset($this->env)) {
      $this->error("\n".'To run a remote install it is necessary to inform the target environment, like --env=development, --env=staging or --env=production'."\n");
      return;
    }
    $this->migrate = $this->option('migrate');
    $this->runRemoteInstall();
  }

  /**
   * Run remove install via url get request
   *
   * @return void
   */
  protected function runRemoteInstall(){
    $url = env("APP_URL");
    $this->info("\n\n"."Running remote install script on $url ...\n");
    if($this->migrate === "true"){
      $url .= "?migrate=true";
    }

    $body = file_get_contents("$url/install.php");

    if($http_response_header[0] === "HTTP/1.1 200 OK"){
      $this->info("\n\n"."Package installed/updated on the server $url\n");
    } else {
      $this->error("The package could be installed");
    }
  }
}
