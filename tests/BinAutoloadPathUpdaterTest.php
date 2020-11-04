<?php

declare(strict_types=1);

namespace WyriHaximus\Tests\Composer;

use Composer\Composer;
use Composer\Config;
use Composer\IO\IOInterface;
use Composer\Package\RootPackage;
use Composer\Repository\RepositoryManager;
use Composer\Repository\WritableRepositoryInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use WyriHaximus\Composer\BinAutoloadPathUpdater;
use WyriHaximus\TestUtilities\TestCase;

final class BinAutoloadPathUpdaterTest extends TestCase
{
    /**
     * @test
     */
    public function update(): void
    {
        $tmpDir = $this->getTmpDir();
        \Safe\file_put_contents($tmpDir . 'bin___app-name.source', '%s');

        $rootPackage = new RootPackage('wyrihaximus/composer-update-bin-autoload-path', '1.0.0', '1.0.0');
        $rootPackage->setExtra([
            'wyrihaximus' => [
                'bin-autoload-path-update' => [
                    'bin___app-name',
                ],
            ],
        ]);


        $composer = new Composer();

        $config = new Config();
        $config->merge(['config' => ['vendor-dir' => $tmpDir . 'vendor']]);
        $composer->setConfig($config);

        $io = $this->prophesize(IOInterface::class);
        $repository        = $this->prophesize(WritableRepositoryInterface::class);
        $repository->getCanonicalPackages()->shouldbeCalled()->willReturn([]);
        $repositoryManager = new RepositoryManager($io->reveal(), $composer->getConfig());
        $repositoryManager->setLocalRepository($repository->reveal());

        $composer->setRepositoryManager($repositoryManager);
        $composer->setPackage($rootPackage);
        $event = new Event(
            ScriptEvents::PRE_AUTOLOAD_DUMP,
            $composer,
            $io->reveal()
        );

        BinAutoloadPathUpdater::updateBinPaths($event);

        self::assertFileExists($tmpDir . 'bin___app-name');
        self::assertSame($tmpDir . 'vendor/autoload.php', file_get_contents($tmpDir . 'bin___app-name'));
    }
}
