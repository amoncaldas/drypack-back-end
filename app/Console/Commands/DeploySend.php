<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class DeploySend extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deploy:send {--osf|only-setup-files=false}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send the package/setup files to a remote server';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
      parent::__construct();
      $this->zipPackFileName = "appPack.zip";
      $this->packageStorage = Storage::disk('package');
      $this->deployStorage = Storage::disk('deploy');
      $this->scriptStorage = Storage::disk('scripts');
      $this->ftpStorage = Storage::disk('ftp');
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
      if ($this->env === null) {
        $this->error("\n".'To send the package it is necessary to inform the target environment, like --env=development, --env=staging or --env=production'."\n");
        return;
      }
      $this->onlySetupFiles = $this->option('only-setup-files');
      $this->send();
    }

    /**
    * Send the package to a remove ftp/sftp server
    *
    * @return void
    */
    protected function send(){
      if($this->mustZip === "false"){
        $this->error("\n".'The options --send and --zip=false can not be used together. Package not sent.'."\n");
        return;
      }
      elseif(env("FTP_HOST") == "ftp.tld" || env("FTP_USER") == "ftp-user" || env("FTP_PASSWD") == "ftp-password") {
        $this->error("\n".'To send the package to the '.$this->env.' environment you must set the FTP/SFTP credentials in the .env.'.$this->env.' file'."\n");
        return;
      }
      else {
        $this->info("\n\n".'Sending to '.env("FTP_HOST")."...\n");

        try{
          if($this->onlySetupFiles === "true"){
            $this->ftpStorage->put("Dockerfile", $this->deployStorage->get("Dockerfile"));
            $this->ftpStorage->put("install-docker.sh", $this->scriptStorage->get("install-docker.sh"));
            $this->ftpStorage->put("docker-compose-runner.yml", $this->deployStorage->get("docker-compose-runner.yml"));
            $this->info("\n\n".'Setup files sent. You can access the server via ssh and run "sh setup-docker.sh" and then "docker-compose -f docker-compose-runner.yml up" to finish the docker container setuo on the remote server'."\n");

          } else {
            $this->ftpStorage->put("install.php", $this->deployStorage->get("install.php"));
            $this->ftpStorage->put($this->zipPackFileName, $this->packageStorage->get($this->zipPackFileName));
          }

          $this->info("\n\n".':::: PACKAGE SENT! ::::'."\n");

        } catch(\Exception $ex){
          $this->error($ex->getMessage());
        }
      }
    }
}
