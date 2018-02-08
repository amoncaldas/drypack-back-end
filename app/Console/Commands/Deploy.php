<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class Deploy extends Command
{
  /**
  * The name and signature of the console command.
  *
  * @var string
  */
  protected $signature = 'deploy {--z|zip=true} {--s|send=false} {--i|install=false} {--rm|rm-samples=false} {--m|migrate=false}';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Script to generate and deploy an application package';

  /**
  * Create a new command instance.
  *
  * @return void
  */
  public function __construct()
  {
    parent::__construct();

    $this->packageStorage = Storage::disk('package');
    $this->deployStorage = Storage::disk('deploy');
    $this->ftpStorage = Storage::disk('ftp');

    $this->packDir = base_path("package");
    $this->tempAppFolderName = "app";
    $this->packAppDir = base_path("package/app");
    $this->zipPackFileName = "appPack.zip";
    $this->dockerProdFileLocation = "docker/production/";
    $this->dockerProdFileName = "Dockerfile";
    $this->zipFullFileLocation = $this->packDir."/".$this->zipPackFileName;
    $this->command = new LinuxCommands();
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
        $this->error("\n".'To deploy the package it is necessary to inform the target environment, like --env=development, --env=staging or --env=production'."\n");
        return;
      }

      $this->send = $this->option('send');
      $this->mustZip = $this->option('zip');

      if($this->mustZip === "false" && $this->send === "true"){
        $this->error("\n".'The --send=true and --zip=false can not be used together. Package not generated.'."\n");
        return;
      }


      $this->install = $this->option('install');
      $this->migrate = $this->option('migrate');
      $this->mustRemoveSamples = $this->option('rm-samples');
      $this->env = $this->option('env'); // can be: development / staging / production
      $this->envFile = $this->env === null? ".env": ".env.".$this->env;

      // Run deploy tasks
      $this->deploy();
  }

  /**
  * Run deploy tasks
  *
  * @return void
  */
  public function deploy(){
    if ($this->env === "none") {
      $this->error("\n".'To run the deploy it is necessary to inform the target environment, like --env=development, --env=staging or --env=production'."\n");
      return;
    }

    $this->info("\n".'Deploying the application...'."\n");
    $env = "--env=".$this->env;

    $zip = "--zip=".$this->mustZip;
    $rm_samples = "--rm-samples=".$this->mustRemoveSamples;
    $this->call("deploy:pack $zip $rm_samples $env");

    if($this->send === "true"){
      $this->call("deploy:send $env");
    }

    if($this->install === "true"){
      $migrate = "--migrate=".$this->migrate;
      $this->call("deploy:install $env $migrate");
    }
  }

}
