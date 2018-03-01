<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class Deploy extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deploy {--no-zip} {--send} {--install} {--rm-samples} {--migrate} {--seed}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Runs the deploy on a target environment';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
      parent::__construct();
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
        $this->error("\n".'To deploy a package it is necessary to inform the target environment, like --env=development, --env=staging or --env=production'."\n");
        return;
      }

      $this->install = $this->option('install');
      $this->deployTarget = env("DEPLOY_TARGET");
      if($this->install === true && ($this->deployTarget === null || $this->deployTarget === "user@host")){
        $this->error("\n".'The DEPLOY_TARGET key is not defined in the .env.'.$this->env.' file. It is necessary to send/install the application. Define it like user@host'."\n");
        return;
      }

      $this->send = $this->install === true? $this->install : $this->option('send');
      $this->mustZip = !$this->option('no-zip');

      if($this->mustZip === false && $this->send === true){
        $this->error("\n".'The --send=true and --no-zip=true can not be used together. Package not generated.'."\n");
        return;
      }

      $this->seed = $this->option('seed');
      $this->migrate = $this->option('migrate');
      $this->mustRemoveSamples = $this->option('rm-samples');
      $this->envFile = $this->env === null? ".env": ".env.".$this->env;

      // Run deploy tasks
      if($this->deploy() === false){
        return;
      }
    }

    /**
  * Run deploy tasks
  *
  * @return void
  */
  public function deploy(){

    $this->info("\n".'Deploying the application...'."\n");

    $parameters = ['--env'=>$this->env];
    if($this->mustZip === false){
        $parameters["--no-zip"] = true;
    }
    if($this->mustRemoveSamples === true){
        $parameters["--rm-samples"] = true;
    }

    $this->call("deploy:pack", $parameters);

    if($this->send === true){
      $this->call("deploy:send", ['--env'=>$this->env]);
    }

    if($this->install === true){
      $this->info("\n".'Installing the package into the remote '.$this->env.' server...'."\n");
      $envoyCommand = "./vendor/bin/envoy run install --target=".$this->deployTarget;

      if($this->migrate === true){
        $envoyCommand .= " --migrate";
      }
      if($this->seed === true){
        $envoyCommand .= " --seed";
      }
      $this->info("\n"."Running '$envoyCommand' command...\n");
      $output = shell_exec($envoyCommand);

      if(strpos($output, "Installation succeeded!") !== false) {
        $this->info("\n".$output."\n");
        $this->info("\n".':::: PACKAGE INSTALLED REMOTELLY! ::::'."\n");
        return true;
      } else {
        $this->error("\n".$output."\n");
        $this->error("\n".':::: REMOTE INSTALLATION FAILED! ::::'."\n");
        return false;
      }
    }
  }

}
