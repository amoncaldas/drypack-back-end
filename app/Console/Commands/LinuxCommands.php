<?php
/**
 * Commands used in console
 *
 * @author Amon Caldas <amoncaldas@gmail.com>
 * @license http://opensource.org/licenses/MIT
 */


namespace App\Console\Commands;

use Illuminate\Support\Facades\File;

class LinuxCommands
{
    /**
     * Check if a file or array of files exist
     *
     * @param string $file
     * @return boolean
     */
    public function checkFile($file){
      if(is_array($file)){
        foreach($file as $f){
            if(!File::exists(base_path($f))){
                return false;
            }
        }
        return true;
      } else {
        return File::exists(base_path($file));
      }
    }

    /**
     * Check if a dir or array of dirs exist
     *
     * @param string|array $dir
     * @return boolean
     */
    public function checkDir($dir){
      if(is_array($dir)){
        foreach($dir as $d){
          if(!File::isDirectory(base_path($d))){
            return false;
          }
        }
        return true;
      } else {
        if(!File::isDirectory(base_path($dir))){
          return false;
        }
        return true;
      }
    }

    /**
     * Copy a file/dir or an array of files/dir from app to package folder
     *
     * @param string|array $source_s
     * @param string $dest
     * @return string $output
     */
    protected function copyFromApp($source_s, $dest){
      if(is_array($source_s)){
        foreach($source_s as $s){
          $source = base_path($s);
          exec("cp -r $source $dest");
        }
      } else {
        $source = base_path($source_s);
        return exec("cp -r $source $dest > /dev/null");
      }
    }

    /**
     * Copy a file or an array of files from app to package folder
     *
     * @param string|array $file_s
     * @param string $dest
     * @return string $output
     */
    public function copyFileFromApp($file_s, $dest){
        $this->copyFromApp($file_s, $dest);
    }

    /**
     * Copy a dir or an array of dirs from app to package folder
     *
     * @param string|array $dir_s
     * @param string $dest
     * @return string $output
     */
    public function copyDirFromApp($dir_s, $dest){
      $this->copyFromApp($dir_s, $dest);
    }

    /**
     * Create a symlink from a file/dir in the app to package file/dir
     *
     * @param string|array $fileOrDir
     * @param string $packDir
     * @return exec output
     */
    public function createPackSymLink($fileOrDir, $packDir){
      $baseDir = base_path();
      if(is_array($fileOrDir)){
        foreach($fileOrDir as $fd){
          $cmd = "if [ ! -L $baseDir/$fd ]; then ln -s $baseDir/$fd $packDir/$fd; fi";
          exec($cmd);
        }
      } else {
        $cmd = "if [ ! -L $baseDir/$fileOrDir ]; then ln -s $baseDir/$fileOrDir $packDir/$fileOrDir; fi";
        return exec($cmd);
      }
    }

    /**
     * Sync folders content
     *
     * @param string $origin
     * @param string $destination
     * @param array $exclusions
     * @return string exec output
     */
    public function syncFolders($origin, $destination, array $exclusions = [] ){
      $excl = "";
      foreach($exclusions as $exclusion){
        if($excl !== ""){
          $excl .= " ";
        }
        $excl .= "--exclude='$exclusion'";
      }
      return exec("rsync -a $excl $origin $destination");
    }

    /**
     * Remove a file or a collection of files
     *
     * @param string|array $file_s
     * @return string exec output
     */
    public function removeFile($file_s){
      if(is_array($file_s)){
        $outputs = "";
        foreach($file_s as $f){
          $outputs .= exec("rm -rf $f");
        }
      } else {
        return exec("rm -rf $file_s");
      }
    }

    /**
     * Remove a dir or a collection of dirs
     *
     * @param string|array $dir_s
     * @return string exec output
     */
    public function removeDir($dir_s){
      if(is_array($dir_s)){
        foreach($dir_s as $d){
          exec("rm -rf $d");
        }
      } else {
        return exec("rm -rf $dir_s");
      }
    }

    /**
     * Remove files from a certain dir based in a pattern
     *
     * @param string $dir
     * @param string $pattern
     * @return string exec output
     */
    public function removeRecursivelyByPattern($dir, $pattern){
      $cmd = "find $dir -name '$pattern' -exec rm -rf {} +";
      return exec($cmd);
    }

    /**
     * Set write permission on dir
     *
     * @param string $dir
     * @return string exec output
     */
    public function setWritePermission($dir){
      if(strpos($dir, "/var") === -1){
        $dir = base_path($dir);
      }
      return exec("chmod -R 777 $dir");
    }

    /**
     * Run an OS command
     *
     * @param string $cmd
     * @return string exec output
     */
    public function runCmd($cmd){
        return exec("$cmd");
    }

    /**
     * Zip the contents fo a given folder
     *
     * @param string $folderName
     * @param string $folderLocation
     * @param string $zipFileName
     * @return string exec output
     */
    public function zipFolderContents($folderLocation, $zipFileName){
      if(strpos($folderLocation, "/var") === -1){
        $folderLocation = base_path($folderLocation);
      }
      if(strpos($zipFileName, ".zip") === -1){
        $zipFileName .= "zip";
      }
      return exec("cd $folderLocation && zip -r -q ../$zipFileName .");
    }
}
