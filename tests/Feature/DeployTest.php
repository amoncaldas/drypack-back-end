<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;

class DeployTest extends TestCase
{

    /**
     * Test artisan app package creation
     */
    public function testGenerateAppPack()
    {
        Artisan::call('deploy:pack', ['--env' => "testing", "--no-zip"=> true]);
        $appFiles = Storage::disk('package')->files('app');
        $this->assertGreaterThanOrEqual(10, $appFiles);
    }

     /**
     * Test artisan app package creation without samples
     */
    public function testGenerateAppPackWithoutSamples()
    {
        Artisan::call('deploy:pack', ['--env' => "testing", "--no-zip"=> true, "--rm-samples"=>true]);
        $appFiles = Storage::disk('package')->files('app');
        $this->assertGreaterThanOrEqual(10, $appFiles);
    }

    /**
     * Test artisan app package zipped creation and sending
     */
    public function testGenerateAppPackZipAndSend()
    {
        // The deploy command internally calls:
        // \Artisan::call('deploy:pack', ['--env' => "testing"]);
        // \Artisan::call('deploy:send', ['--env' => "testing"]);
        Artisan::call('deploy:run', ['--env' => "testing", "--send"=>true]);

        // The package is generated and stored locally before being sent to the server
        // and is stored at the package folder
        $this->assertEquals(Storage::disk('package')->exists('appPack.zip'), true);

        // When running in testing env, the ouput to the ftp disk is redirected
        // to a local virtual ftp folder. So, after "sending" the package to
        // the "ftp", we check if the expected files are on this folder
        $this->assertEquals(Storage::disk('ftp')->exists('appPack.zip'), true);
        $this->assertEquals(Storage::disk('ftp')->exists('install.sh'), true);
        $this->assertEquals(Storage::disk('ftp')->exists('install-docker.sh'), true);
        $this->assertEquals(Storage::disk('ftp')->exists('docker-compose.yml'), true);

        // After check that the expected files are there, we remove them
        Storage::disk('ftp')->delete('appPack.zip');
        Storage::disk('ftp')->delete('install.sh');
        Storage::disk('ftp')->delete('install-docker.sh');
        Storage::disk('ftp')->delete('docker-compose.yml');
    }

    /**
     * Test the single file sending command
     */
    public function testSendingASingleFile(){
        Artisan::call('deploy:send', ['--env' => "testing", "--single-file"=>"docs/add-key-remote-ssh.md"]);

        // When running in testing env, the ouput to the ftp disk is redirected
        // to a local virtual ftp folder. So, after "sending" the file to
        // the "ftp", we check if the expected file is on this folder
        $this->assertEquals(true, Storage::disk('ftp')->exists('docs/add-key-remote-ssh.md'));

        // After check that the expected file is there, we remove it
        Storage::disk('ftp')->delete('docs/add-key-remote-ssh.md');
    }

    /**
     * Test artisan deply with real world sending and installation on a remote server
     * This test is commented by default because it depends on real world credentials to FTP and
     * DEPLOY_TARGET on the .env.testing file. If you put correct data to these keys on the .env.testing
     * file and want to test a real deployment, then uncomment the method below
     */
    // public function testGenerateAppPackZipSendAndInstall(){
    //     Artisan::call('deploy:run', ['--env' => "testing", "--install"=>true, "--migrate"=>true, "--seed"=>true]);
    //     $this->assertEquals(strpos(true, \Artisan::output(), "Installation succeeded!"));
    // }
}
