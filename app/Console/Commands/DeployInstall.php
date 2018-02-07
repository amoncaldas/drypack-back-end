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
    if ($this->env === null) {
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
    $this->info("\n\n"."Running remote install script on $url...\n");
    $url = env("APP_URL");
    if($this->migrate === "true"){
      $url .= "?migrate=true";
    }

    $client = new Client(['timeout'  => 10, 'connect_timeout' => 10]);
    $promise = $client->requestAsync('GET', $url);
    $failMessage = "\n\n"."Open the browser in the url $url to run the installer on the server and finish the process\n";

    $promise->then(
        function (ResponseInterface $res) {
          if($res->getStatusCode()=== 200){
            $this->info("\n\n"."Package installed/updated on the server $url\n");
          } else {
            $this->info($failMessage);
          }
        },
        function (RequestException $e) {
          $this->info($failMessage);
        }
    );
    //$result = $client->get($url."/package/install.php");

  }
}
