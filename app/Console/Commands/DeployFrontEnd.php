<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use App\Console\Commands\LinuxCommands;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Filesystem\FilesystemManager;

class DeployFrontEnd extends Command
{
  /**
   * The name and signature of the console command.
   * --client is the desired front-end to deploy, like client or admin
   * --strategy defines whick type of front-end we should build, like angular1, angular2, vuejs2 etc
   * --out-folder is where the front-end will be put
   *
   * @var string
   */
  protected $signature = 'deploy:front-end {--client=} {--strategy=} {--out-folder=} {--rm-samples}';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Extract, build and save a selected front-end application in a target local folder';

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

    $client = $this->option('client');
    $strategy = $this->option('strategy');
    $outputFolder = $this->option('out-folder');
    $this->mustRemoveSamples = $this->option('rm-samples');
    $method = "copyAndBuildFrontend".ucfirst($strategy);
    $this->command = new LinuxCommands();

    if (!isset($this->env)) {
      $this->error("\n".'To extract a front-end app it is necessary to inform the target environment, like --env=development, --env=staging or --env=production'."\n");
      return;
    }

    if ($this->command->checkDir("public/$client") === true) {
      $this->resetOutputFolder($client, $outputFolder);
      $this->$method($client, $outputFolder);
      $this->info("\n\n".":::: ".strtoupper($client)." FRONT-END EXTRACTED SUCCESSFULY! ::::"."\n");
      $this->info("\n"."The front-end app is available at $outputFolder/$client"."\n");
    }else {
      $this->error("\n"."Front-end $client not found at public/$client"."\n");
    }
  }

    /**
   * Copy front-end files to temp package dir
   *
   * @param string $client (client|admin)
   * @return void
   */
  protected function copyAndBuildFrontendAngular1($client, $outputFolder){
    $this->info("\n\n".'Building and copying '.$client.' front-end files...'."\n");

    $bar = $this->output->createProgressBar(3);

    $result = $this->command->checkFile("public/$client/gulpfile.js");
    if($result !== true){
      $this->exitError("The directory ($result) was not found. It is not possible to continue.");
    }

    File::makeDirectory($outputFolder."/public/$client/", 0777, true, true);
    $this->command->copyDirFromApp(["public/$client/app", "public/$client/images", "public/$client/styles"], $outputFolder."/public/$client");
    $this->command->copyFileFromApp(["public/$client/gulpfile.js", "public/$client/index.html", "public/$client/paths.json"], $outputFolder."/public/$client");
    $this->command->createPackSymLink("public/$client/node_modules", $outputFolder);
    File::makeDirectory($outputFolder."/public/$client/build", 0777, true, true);
    $bar->advance();

    //define if the front-end must be built in production mode or not
    $envParam = $this->env === "production" || $this->env === "staging"? "--production": "";

    $this->command->runCmd("cd ". $outputFolder."/public/$client/ && npm rebuild node-sass >> /dev/null 2>&1 && gulp build $envParam >> /dev/null 2>&1");
    $this->command->removeDir($outputFolder."/public/$client/node_modules");
    $bar->advance();

    // angular i18n use several files to be able to switch to several languages/cultures
    // These files are only loaded when a user selects a language, so they all are not
    // included in the minimized js and then it is needed to include the entire folder with its contents
    if($this->command->checkDir("public/$client/node_modules/angular-i18n") === true){
      File::makeDirectory($outputFolder."/public/$client/node_modules");
      $this->command->copyDirFromApp("public/$client/node_modules/angular-i18n", $outputFolder."/public/$client/node_modules/");
    }

    $this->command->removeRecursivelyByPattern($outputFolder."/public/$client/", ".*");
    $this->command->removeRecursivelyByPattern($outputFolder."/public/$client/", "*.example*");

    // After compiling/minimizing every javascript in a single application.js,
    // we can remove the sources js, they are not needed anymore to run the application
    $this->command->removeFile(
      [
        $outputFolder."/public/$client/app/*.js",
        $outputFolder."/public/$client/app/**/*.js",
        $outputFolder."/public/$client/app/**/**/*.js",
        $outputFolder."/public/$client/styles/*.scss",
        $outputFolder."/public/$client/gulpfile.js",
      ]
    );

    if($this->mustRemoveSamples === "true"){
      $this->command->removeDir($outputFolder."/public/$client/app/samples");
    }

    // After removing javascript files as result maybe we have some empty folders, so we remove them
    $this->RemoveEmptySubFolders($outputFolder."/public/$client");

    $this->command->setWritePermission($outputFolder);
    $bar->advance();
    $bar->finish();

  }

   /**
   * Reset the package dir, removing previous package generated
   *
   * @return void
   */
  protected function resetOutputFolder($client, $outputFolder){
    $destination = "$outputFolder/$client";

    $this->info("\n"."Removing previous $destination front-end extracted..."."\n");

    if(!File::exists($destination)) {
      File::makeDirectory($destination);
      $this->command->setWritePermission($destination);
    }
    File::cleanDirectory($destination);
  }

  /**
   * Remove empty sub folders from a folder
   *
   * @param string $path
   * @return void
   */
  protected function RemoveEmptySubFolders($path)
  {
    $empty=true;
    foreach (glob($path.DIRECTORY_SEPARATOR."*") as $file)
    {
      $empty &= is_dir($file) && !is_link($file) && $this->RemoveEmptySubFolders($file);
    }
    return $empty &&  rmdir($path);
  }
}
