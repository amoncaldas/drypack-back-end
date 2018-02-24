<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

class DeployTest extends TestCase
{

    /**
     * Test artisan app package creation
     */
    public function testGenerateAppPack()
    {
        \Artisan::callSilent('deploy:pack', ['--env' => "testing", "--no-zip"=> true]);
        $appFiles = Storage::disk('package')->files('app');
        $this->assertGreaterThanOrEqual(10, $appFiles);
    }

     /**
     * Test artisan app package creation without samples
     */
    public function testGenerateAppPackWithoutSamples()
    {
        \Artisan::callSilent('deploy:pack', ['--env' => "testing", "--no-zip"=> true, "--rm-samples"=>true]);
        $appFiles = Storage::disk('package')->files('app');
        $this->assertGreaterThanOrEqual(10, $appFiles);
    }

    /**
     * Test artisan app package zipped creation and sending
     */
    public function testGenerateAppPackZippedAndSend()
    {
        \Artisan::callSilent('deploy:pack', ['--env' => "testing"]);
        $this->assertEquals(Storage::disk('package')->exists('appPack.zip'), true);
        \Artisan::call('deploy:send', ['--env' => "testing"]);

        $this->assertEquals(Storage::disk('ftp')->exists('appPack.zip'), true);
        $this->assertEquals(Storage::disk('ftp')->exists('install.sh'), true);
        $this->assertEquals(Storage::disk('ftp')->exists('install-docker.sh'), true);
        $this->assertEquals(Storage::disk('ftp')->exists('docker-compose.yml'), true);

        // after check, we remove the files
        Storage::disk('ftp')->delete('appPack.zip');
        Storage::disk('ftp')->delete('install.sh');
        Storage::disk('ftp')->delete('install-docker.sh');
        Storage::disk('ftp')->delete('docker-compose.yml');

        // test also single file
        //'deploy:send {--single-file=}';
    }

}
