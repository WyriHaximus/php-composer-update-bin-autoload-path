<?php

declare(strict_types=1);

namespace WyriHaximus\Tests\Composer;

use Composer\Composer;
use Composer\Config;
use Composer\IO\IOInterface;
use Composer\Package\Package;
use Composer\Package\RootPackage;
use Composer\Repository\RepositoryManager;
use Composer\Repository\WritableRepositoryInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use WyriHaximus\Composer\BinAutoloadPathUpdater;
use WyriHaximus\TestUtilities\TestCase;

use function Safe\file_get_contents;
use function Safe\file_put_contents;
use function Safe\mkdir;

final class BinAutoloadPathUpdaterTest extends TestCase
{
    /**
     * @test
     */
    public function update(): void
    {
        $tmpDir = $this->getTmpDir();
        mkdir($tmpDir . 'bin', 0777, true);
        file_put_contents($tmpDir . 'bin/app-name.source', '%s');
        mkdir($tmpDir . 'vendor/wyrihaximus/b/bin', 0777, true);
        file_put_contents($tmpDir . 'vendor/wyrihaximus/b/bin/app-b-name.source', '%s');

        $rootPackage = new RootPackage('wyrihaximus/composer-update-bin-autoload-path', '1.0.0', '1.0.0');
        $rootPackage->setExtra([
            'wyrihaximus' => [
                'bin-autoload-path-update' => ['bin/app-name'],
            ],
        ]);

        $packageA = new Package('wyrihaximus/a', '1.0.0', '1.0.0');
        $packageA->setExtra([
            'wyrihaximus' => ['lol' => 'nope'],
        ]);

        $packageB = new Package('wyrihaximus/b', '1.0.0', '1.0.0');
        $packageB->setExtra([
            'wyrihaximus' => [
                'bin-autoload-path-update' => ['bin/app-b-name'],
            ],
        ]);

        $packageC = new Package('wyrihaximus/c', '1.0.0', '1.0.0');
        $packageC->setExtra([]);

        $composer = new Composer();

        $config = new Config();
        $config->merge(['config' => ['vendor-dir' => $tmpDir . 'vendor']]);
        $composer->setConfig($config);

        $io         = $this->prophesize(IOInterface::class);
        $repository = $this->prophesize(WritableRepositoryInterface::class);
        $repository->getCanonicalPackages()->shouldBeCalled()->willReturn([$packageA, $packageB, $packageC]);
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

        self::assertFileExists($tmpDir . 'bin/app-name');
        self::assertFileExists($tmpDir . 'vendor/wyrihaximus/b/bin/app-b-name');
        self::assertSame('../vendor/autoload.php', file_get_contents($tmpDir . 'bin/app-name'));
        self::assertSame('../../../autoload.php', file_get_contents($tmpDir . 'vendor/wyrihaximus/b/bin/app-b-name'));
    }

    /**
     * @test
     */
    public function activate(): void
    {
        $io = $this->prophesize(IOInterface::class);
        $io->isInteractive()->shouldNotBeCalled();
        (new BinAutoloadPathUpdater())->activate(new Composer(), $io->reveal());
    }

    /**
     * @test
     */
    public function deactivate(): void
    {
        $io = $this->prophesize(IOInterface::class);
        $io->isInteractive()->shouldNotBeCalled();
        (new BinAutoloadPathUpdater())->deactivate(new Composer(), $io->reveal());
    }

    /**
     * @test
     */
    public function uninstall(): void
    {
        $io = $this->prophesize(IOInterface::class);
        $io->isInteractive()->shouldNotBeCalled();
        (new BinAutoloadPathUpdater())->uninstall(new Composer(), $io->reveal());
    }
}
