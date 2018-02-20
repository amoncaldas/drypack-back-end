<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use App\Console\Commands\LinuxCommands;
use Illuminate\Support\Facades\App;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Filesystem\FilesystemManager;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\RequestException;

class DeployPack extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'deploy:pack {--no-zip} {--rm-samples}';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Generate an application package';

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
      $this->error("\n".'To generate the package it is necessary to inform the target environment, like --env=development, --env=staging or --env=production'."\n");
      return;
    }

    $this->envFile = $this->env === null? ".env": ".env.".$this->env;
    $this->mustZip = !$this->option('no-zip');
    $this->mustRemoveSamples = $this->option('rm-samples');



    $steps = 5;
    if($this->mustZip === true){
        $steps++;
    }

    $this->info("\n".':::: STARTED PACKAGE GENERATION ::::'."\n");

    $this->resetPackDirs();
    $this->clearApp();
    $this->copyBackendFiles();
    $this->copyAndBuildFrontend("client");
    $this->copyAndBuildFrontend("admin");
    if($this->mustZip === true){
      $this->zip();
      $this->info("\n\n".'See zip package at '.$this->zipFullFileLocation."\n");

    } else {
      $this->info("\n\n".'See package folder at '.$this->packAppDir."\n");
    }
    $this->info("\n\n".':::: PACKAGE GENERATED! ::::'."\n");
  }

  /**
   * Remove the temp/pack files  generated, print an error message and stop the exception
   *
   * @param string $error
   * @return void
   */
  protected function exitError($error){
    $this->resetPackDirs();
    $this->error($error);
    exit;
  }

  /**
   * clean app cache files
   *
   * @return void
   */
  protected function clearApp(){

    $bar = $this->output->createProgressBar(5);
    $this->info("\n".'Cleaning cache and logs...'."\n");

    $this->call("cache:clear");
    $bar->advance();
    $this->call("route:clear");
    $bar->advance();
    $this->call("view:clear");
    $bar->advance();
    $this->call("config:clear");
    $bar->advance();
    $this->call("clear-compiled");
    $bar->finish();
  }

  /**
   * Reset the package dir, removing previous package generated
   *
   * @return void
   */
  protected function resetPackDirs(){
    $this->info("\n".'Removing previous package generated...'."\n");
    if(!File::exists($this->packDir)) {
      File::makeDirectory($this->packDir);
      $this->command->setWritePermission($this->packDir);
    }
    File::cleanDirectory($this->packDir);

    if(!File::exists($this->packAppDir)) {
        File::makeDirectory($this->packAppDir);
        $this->command->setWritePermission($this->packAppDir);
      }
      File::cleanDirectory($this->packAppDir);
      File::put($this->packAppDir.'/.gitkeep', "");
  }

  /**
   * Copy back-end files to temp package dir
   *
   * @return void
   */
  protected function copyBackendFiles(){
    $this->info("\n\n".'Copying back-end files to temp folder...'."\n");

    $bar = $this->output->createProgressBar(4);
    File::copy(base_path($this->envFile),  $this->packAppDir."/.env");
    $bar->advance();

    $result = $this->command->checkDir(["app", "bootstrap", "config", "public", "resources", "storage", "vendor"]);
    if($result !== true){
      $this->exitError("The directory ($result) was not found. It is not possible to continue.");
    }
    $bar->advance();

    $this->command->copyDirFromApp(["bootstrap", "storage"], $this->packAppDir);
    $this->command->copyFileFromApp("artisan", $this->packAppDir);

    File::makeDirectory($this->packAppDir."/public", 0777, true, true);
    $this->command->copyFileFromApp(
      [
        "public/favicon.ico",
        "public/index.php",
        "public/robots.txt"
      ],
      $this->packAppDir."/public"
    );
    $bar->advance();

    $backendAppDirs = ["app", "config", "resources", "vendor", "routes", "database"];
    // If the package will, at the end, be zipped, we don't need to copy
    // but symlink them and when zipping, they are included
    if($this->mustZip === true){
      $this->command->createPackSymLink($backendAppDirs, $this->packAppDir);
    } else { // if is not gonna be zipped, we copy them
      $this->command->copyDirFromApp($backendAppDirs, $this->packAppDir);
    }
    $this->command->removeFile($this->packAppDir."/.gitignore");
    $bar->advance();
    $bar->finish();
  }

  /**
   * Copy front-end files to temp package dir
   *
   * @param string $client (client|admin)
   * @return void
   */
  protected function copyAndBuildFrontend($client){
    if ($this->command->checkDir("public/$client") === true) {

      $this->info("\n\n".'Building and copying '.$client.' front-end files...'."\n");

      $bar = $this->output->createProgressBar(3);

      $result = $this->command->checkFile("public/$client/gulpfile.js");
      if($result !== true){
        $this->exitError("The directory ($result) was not found. It is not possible to continue.");
      }

      File::makeDirectory($this->packAppDir."/public/$client/", 0777, true, true);
      $this->command->copyDirFromApp(["public/$client/app", "public/$client/images", "public/$client/styles"], $this->packAppDir."/public/$client");
      $this->command->copyFileFromApp(["public/$client/gulpfile.js", "public/$client/index.html", "public/$client/paths.json"], $this->packAppDir."/public/$client");
      $this->command->createPackSymLink("public/$client/node_modules", $this->packAppDir);
      File::makeDirectory($this->packAppDir."/public/$client/build", 0777, true, true);
      $bar->advance();

      //define if the front-end must be built in production mode or not
      $envParam = $this->env === "production" || $this->env === "staging"? "--production": "";

      $this->command->runCmd("cd ". $this->packAppDir."/public/$client/ && npm rebuild node-sass && gulp build $envParam");
      $this->command->removeDir($this->packAppDir."/public/$client/node_modules");
      $bar->advance();

      // angular i18n use several files to be able to switch to several languages/cultures
      // These files are only loaded when a user selects a language, so they all are not
      // included in the minimized js and then it is needed to include the entire folder with its contents
      if($this->command->checkDir("public/$client/node_modules/angular-i18n") === true){
        File::makeDirectory($this->packAppDir."/public/$client/node_modules");
        $this->command->copyDirFromApp("public/$client/node_modules/angular-i18n", $this->packAppDir."/public/$client/node_modules/");
      }

      $this->command->removeRecursivelyByPattern($this->packAppDir."/public/$client/", ".*");
      $this->command->removeRecursivelyByPattern($this->packAppDir."/public/$client/", "*.example*");

      // After compiling/minimizing every javascript in a single application.js,
      // we can remove the sources js, they are not needed anymore to run the application
      $this->command->removeFile(
        [
          $this->packAppDir."/public/$client/app/*.js",
          $this->packAppDir."/public/$client/app/**/*.js",
          $this->packAppDir."/public/$client/app/**/**/*.js",
          $this->packAppDir."/public/$client/styles/*.scss",
          $this->packAppDir."/public/$client/gulpfile.js",
        ]
      );

      if($this->mustRemoveSamples === "true"){
        $this->command->removeDir($this->packAppDir."/public/$client/app/samples");
      }

      // After removing javascript files as result maybe we have some empty folders, so we remove them
      $this->RemoveEmptySubFolders($this->packAppDir."/public/$client");

      $this->command->setWritePermission($this->packAppDir);
      $bar->advance();
      $bar->finish();
    }
  }

  /**
   * Zip the package dir
   *
   * @return void
   */
  protected function zip(){

    $zipFullFileLocation = $this->packDir."/".$this->zipPackFileName;

    $this->info("\n\n".'Zipping the package...'."\n");

    $bar = $this->output->createProgressBar(3);

    $this->command->setWritePermission($this->packAppDir);
    $bar->advance();

    $folderLocation = $this->packDir."/".$this->tempAppFolderName;
    $this->command->zipFolderContents($folderLocation, $this->zipPackFileName);
    $bar->advance();

    $this->command->setWritePermission($this->packDir);
    $this->command->setWritePermission($this->packAppDir);
    $bar->advance();

    File::cleanDirectory($this->packAppDir);
    File::put($this->packAppDir.'/.gitkeep', "");

    $bar->finish();
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
