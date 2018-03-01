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
    protected $signature = 'deploy:send {--single-file=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send the package, setup files or single files to a remote server';

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
      $this->scriptStorage = Storage::disk('scripts');
      $this->ftpStorage = Storage::disk('ftp');
      $this->rootStorage = Storage::disk('root');

      $this->zipPackFileName = "appPack.zip";
      $this->installerFileName = "install.sh";
      $this->dockerInstallScript = "install-docker.sh";
      $this->dockerComposeFileName = "docker-compose.yml";
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
        $this->error("\n".'To send the package it is necessary to inform the target environment, like --env=development, --env=staging or --env=production'."\n");
        return;
      }
      if(env("FTP_HOST") == "ftp.tld" || env("FTP_USER") == "ftp-user" || env("FTP_PASSWD") == "ftp-password") {
        $this->error("\n".'To send the package to the '.$this->env.' environment you must set the FTP/SFTP credentials in the .env.'.$this->env.' file'."\n");
        return;
      }
      $this->singleFile = $this->option('single-file');
      $this->send();
    }

    /**
    * Send the package to a remove ftp/sftp server
    *
    * @return void
    */
    protected function send(){
      $this->info("\n\n".'Sending to '.$this->env.' server at '.env("FTP_HOST")." ...\n");

      try{
        // sent a single file
        if(isset($this->singleFile)) {
          $this->sendSingleFile();
        }
        // sent package and installer file
        else {
          $this->sendPackage();
        }
      } catch(\Exception $ex){
        $this->error($ex->getMessage());
      }
    }


    /**
     * Send a single file
     *
     * @return void
     */
    protected function sendSingleFile(){
      $filePath = $this->singleFile;
      try{
        $fileContent = $this->rootStorage->get($this->singleFile);
      } catch(\Exception $ex){
        $this->error("\n\n"."The file $filePath could not be located!"."\n");
        return;
      }

      if($this->ftpStorage->put($filePath, $fileContent) === false){
        $this->sendingError($file);
        return;
      }
      $this->info("\n\n"."File $filePath send successfully!"."\n");
    }

    /**
     * Send the package and the installer
     *
     * @return void
     */
    protected function sendPackage(){
      if($this->ftpStorage->put($this->dockerInstallScript, $this->scriptStorage->get($this->dockerInstallScript)) === false){
        $this->sendingError($this->dockerInstallScript);
        return;
      }
      if($this->ftpStorage->put($this->dockerComposeFileName, $this->deployStorage->get($this->dockerComposeFileName)) === false){
        $this->sendingError($this->dockerComposeFileName);
        return;
      }
      if($this->ftpStorage->put($this->installerFileName, $this->deployStorage->get($this->installerFileName)) === false){
        $this->sendingError($this->installerFileName);
        return;
      }
      if($this->ftpStorage->put($this->zipPackFileName, $this->packageStorage->get($this->zipPackFileName)) === false){
        $this->sendingError($this->zipPackFileName);
        return;
      }
      $this->info("\n\n".':::: PACKAGE SENT! ::::'."\n");
      $this->info("\n\n".'You can now access the server via ssh and run "sh '.$this->installerFileName.' to finish the instalation.'."\n");
    }

    /**
     * Print an error message in case that a file can not be sent
     *
     * @param string $fileName
     * @return void
     */
    protected function sendingError($fileName){
        $this->error("\n".'The file '.$fileName.' could not be sent. Sending aborted.'."\n");
    }
}
